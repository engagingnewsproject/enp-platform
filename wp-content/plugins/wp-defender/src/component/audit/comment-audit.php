<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Audit;

use WP_Defender\Traits\User;
use WP_Defender\Model\Audit_Log;

class Comment_Audit extends Audit_Event {
	use User;

	const ACTION_SPAMMED  = 'spammed', ACTION_UNSPAMMED = 'unspammed', ACTION_DUPLICATED = 'duplicated', ACTION_FLOOD = 'flood', ACTION_ADDED = 'added';
	const CONTEXT_COMMENT = 'ct_comment';

	/**
	 * @return array
	 */
	public function get_hooks() {

		return array(
			'wp_insert_comment'         => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_ADDED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'comment_flood_trigger'     => array(
				'args'        => array( 'time_lastcomment', 'time_newcomment' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_FLOOD,
				'context'     => self::CONTEXT_COMMENT,
				'text'        => sprintf(
				/* translators: */
					esc_html__( '%1$s User %2$s flooded comment', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}'
				),
			),
			'deleted_comment'           => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_DELETED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'trash_comment'             => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_TRASHED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'untrash_comment'           => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_RESTORED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'spam_comment'              => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_SPAMMED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'unspam_comment'            => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_UNSPAMMED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'transition_comment_status' => array(
				'args'        => array( 'new_status', 'old_status', 'comment' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_UPDATED,
				'callback'    => array(
					self::class,
					'process_comment_status_changed',
				),
			),
			'edit_comment'              => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_UPDATED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'comment_duplicate_trigger' => array(
				'args'        => array( 'commentdata' ),
				'event_type'  => Audit_Log::EVENT_TYPE_COMMENT,
				'action_type' => self::ACTION_DUPLICATED,
				'callback'    => array(
					self::class,
					'process_comment_duplicate_trigger',
				),
			),
		);
	}

	/**
	 * @return array
	 */
	public function dictionary() {

		return array(
			self::ACTION_DUPLICATED => esc_html__( 'Duplicated', 'wpdef' ),
			self::ACTION_SPAMMED    => esc_html__( 'Spammed', 'wpdef' ),
			self::ACTION_UNSPAMMED  => esc_html__( 'Unspammed', 'wpdef' ),
			self::CONTEXT_COMMENT   => esc_html__( 'Comment', 'wpdef' ),
		);
	}

	/**
	 * @return array
	 */
	public function process_comment_duplicate_trigger() {
		$args         = func_get_args();
		$comment_data = $args[1]['commentdata'];

		$post            = get_post( $comment_data['comment_post_ID'] );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		$blog_name       = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		$text            = sprintf(
		/* translators: */
			esc_html__( '%1$s User %2$s submitted a duplicate comment on %3$s "%4$s"', 'wpdef' ),
			$blog_name,
			is_user_logged_in() ? $this->get_user_display( get_current_user_id() ) : $comment_data['comment_author'],
			$post_type_label,
			$post->post_title
		);

		return array( $text, $post_type_label );
	}

	/**
	 * @return array
	 */
	public function process_comment_status_changed() {
		$args            = func_get_args();
		$new_stat        = $args[1]['new_status'];
		$old_stat        = $args[1]['old_status'];
		$comment         = $args[1]['comment'];
		$post            = get_post( $comment->comment_post_ID );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		$blog_name       = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		$text            = false;
		if ( 'unapproved' === $old_stat && 'approved' === $new_stat ) {
			$text = sprintf(
			/* translators: */
				esc_html__( '%1$s %2$s approved comment ID %3$s from %4$s, on %5$s "%6$s"', 'wpdef' ),
				$blog_name,
				$this->get_user_display( get_current_user_id() ),
				$comment->comment_ID,
				$comment->comment_author,
				$post_type_label,
				$post->post_title
			);
		} elseif ( 'unapproved' === $new_stat && 'approved' === $old_stat ) {
			$text = sprintf(
			/* translators: */
				esc_html__( '%1$s %2$s unapproved comment ID %3$s from %4$s, on %5$s "%6$s"', 'wpdef' ),
				$blog_name,
				$this->get_user_display( get_current_user_id() ),
				$comment->comment_ID,
				$comment->comment_author,
				$post_type_label,
				$post->post_title
			);
		}

		return array( $text, $post_type_label );
	}

	/**
	 * @return bool|array
	 */
	public function process_generic_comment() {
		$args       = func_get_args();
		$hookname   = $args[0];
		$comment_id = $args[1]['comment_ID'];
		if ( ! isset( $args[1]['commentdata'] ) ) {
			$comment = get_comment( $comment_id );
			if ( empty( $comment ) ) {
				return false;
			}
			$comment = $comment->to_array();
		} else {
			$comment = $args[1]['commentdata'];
			if ( empty( $comment ) ) {
				return false;
			}
		}
		if ( ! isset( $args[1]['comment_approved'] ) ) {
			$comment_approved = '';
		} else {
			$comment_approved = $args[1]['comment_approved'];
		}
		$post            = get_post( $comment['comment_post_ID'] );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		$blog_name       = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		switch ( $hookname ) {
			case 'comment_post':
			case 'wp_insert_comment':
				if ( 'spam' === $comment_approved ) {
					$comment_status = 'spam';
				} elseif ( 1 === $comment_approved ) {
					$comment_status = __( 'approved', 'wpdef' );
				} else {
					$comment_status = __( 'pending approval', 'wpdef' );
				}
				if ( 0 == $comment['comment_parent'] ) {
					$text = sprintf(
					/* translators: */
						__( '%1$s %2$s commented on %3$s "%4$s" - comment status: %5$s', 'wpdef' ),
						$blog_name,
						$comment['comment_author'],
						$post_type_label,
						$post->post_title,
						$comment_status
					);
				} else {
					$parent_comment = get_comment( $comment['comment_parent'] );
					$text           = sprintf(
					/* translators: */
						__( "%1\$s %2\$s replied to %3\$s's comment on %4\$s \"%5\$s\" - comment status: %6\$s", 'wpdef' ),
						$blog_name,
						$comment['comment_author'],
						$parent_comment->comment_author,
						$post_type_label,
						$post->post_title,
						$comment_status
					);
				}
				break;
			case 'deleted_comment':
				$text = sprintf(
				/* translators: */
					__( '%1$s %2$s deleted comment ID %3$s, comment author: %4$s on %5$s "%6$s"', 'wpdef' ),
					$blog_name,
					$this->get_user_display( get_current_user_id() ),
					$comment_id,
					$comment['comment_author'],
					$post_type_label,
					$post->post_title
				);
				break;
			case 'trash_comment':
				$text = sprintf(
				/* translators: */
					__( '%1$s %2$s trashed comment ID %3$s, comment author: %4$s on %5$s "%6$s"', 'wpdef' ),
					$blog_name,
					$this->get_user_display( get_current_user_id() ),
					$comment_id,
					$comment['comment_author'],
					$post_type_label,
					$post->post_title
				);
				break;
			case 'untrash_comment':
				$text = sprintf(
				/* translators: */
					__( '%1$s %2$s untrashed comment ID %3$s, comment author: %4$s on %5$s "%6$s"', 'wpdef' ),
					$blog_name,
					$this->get_user_display( get_current_user_id() ),
					$comment_id,
					$comment['comment_author'],
					$post_type_label,
					$post->post_title
				);
				break;
			case 'spam_comment':
				$text = sprintf(
				/* translators: */
					__( '%1$s %2$s marked comment ID %3$s, comment author: %4$s on %5$s "%6$s" as spam', 'wpdef' ),
					$blog_name,
					$this->get_user_display( get_current_user_id() ),
					$comment_id,
					$comment['comment_author'],
					$post_type_label,
					$post->post_title
				);
				break;
			case 'unspam_comment':
				$text = sprintf(
				/* translators: */
					__( '%1$s %2$s unmarked comment ID %3$s, comment author: %4$s on %5$s "%6$s" as spam', 'wpdef' ),
					$blog_name,
					$this->get_user_display( get_current_user_id() ),
					$comment_id,
					$comment['comment_author'],
					$post_type_label,
					$post->post_title
				);
				break;
			case 'edit_comment':
				$text = sprintf(
				/* translators: */
					__( '%1$s %2$s edited comment ID %3$s, comment author: %4$s on %5$s "%6$s"', 'wpdef' ),
					$blog_name,
					$this->get_user_display( get_current_user_id() ),
					$comment_id,
					$comment['comment_author'],
					$post_type_label,
					$post->post_title
				);
				break;
			default:
				return false;
		}

		return array( $text, $post_type_label );
	}
}