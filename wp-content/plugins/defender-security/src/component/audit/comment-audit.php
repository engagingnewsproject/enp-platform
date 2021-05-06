<?php

namespace WP_Defender\Component\Audit;

use WP_Defender\Traits\User;

/**
 * Author: Hoang Ngo
 */
class Comment_Audit extends Audit_Event {
	use User;

	const ACTION_SPAMMED = 'spammed', ACTION_UNSPAMMED = 'unspammed', ACTION_DUPLICATED = 'duplicated', ACTION_FLOOD = 'flood', ACTION_ADDED = 'added';
	const CONTEXT_COMMENT = 'ct_comment';
	protected $type = 'comment';

	public function get_hooks() {
		return array(
			'wp_insert_comment'         => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_ADDED,
				'callback'    => array( self::class, 'process_generic_comment' )
			),
			'comment_flood_trigger'     => array(
				'args'        => array( 'time_lastcomment', 'time_newcomment' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_FLOOD,
				'context'     => self::CONTEXT_COMMENT,
				'text'        => sprintf( esc_html__( "User %s flooded comment", 'wpdef' ), '{{wp_user}}' ),
			),
			'deleted_comment'           => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_DELETED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'trash_comment'             => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_TRASHED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'untrash_comment'           => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_RESTORED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'spam_comment'              => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_SPAMMED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'unspam_comment'            => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_UNSPAMMED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'transition_comment_status' => array(
				'args'        => array( 'new_status', 'old_status', 'comment' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_UPDATED,
				'callback'    => array(
					self::class,
					'process_comment_status_changed'
				),
			),
			'edit_comment'              => array(
				'args'        => array( 'comment_ID' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_UPDATED,
				'callback'    => array( self::class, 'process_generic_comment' ),
			),
			'comment_duplicate_trigger' => array(
				'args'        => array( 'commentdata' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_DUPLICATED,
				'callback'    => array(
					self::class,
					'process_comment_duplicate_trigger'
				),
			),
		);
	}

	/**
	 * @return array
	 */
	public function dictionary() {
		return array(
			self::ACTION_DUPLICATED => esc_html__( "Duplicated", 'wpdef' ),
			self::ACTION_SPAMMED    => esc_html__( "Spammed", 'wpdef' ),
			self::ACTION_UNSPAMMED  => esc_html__( "Unspammed", 'wpdef' ),
			self::CONTEXT_COMMENT   => esc_html__( "Comment", 'wpdef' )
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
		$text            = sprintf( esc_html__( 'User %s submitted a duplicate comment on %s "%s"', 'wpdef' ),
			is_user_logged_in() ? $this->get_user_display( get_current_user_id() ) : $comment_data['comment_author'],
			$post_type_label, $post->post_title );

		return array( $text, $post_type_label );
	}

	/**
	 * @return bool|string
	 */
	public function process_comment_status_changed() {
		$args            = func_get_args();
		$new_stat        = $args[1]['new_status'];
		$old_stat        = $args[1]['old_status'];
		$comment         = $args[1]['comment'];
		$post            = get_post( $comment->comment_post_ID );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		$text            = false;
		if ( $old_stat == 'unapproved' && $new_stat == 'approved' ) {
			$text = sprintf( esc_html__( "%s approved comment ID %s from %s, on %s \"%s\"", 'wpdef' ),
				$this->get_user_display( get_current_user_id() ), $comment->comment_ID, $comment->comment_author,
				$post_type_label, $post->post_title );
		} elseif ( $new_stat == 'unapproved' && $old_stat == 'approved' ) {
			$text = sprintf( esc_html__( "%s unapproved comment ID %s from %s, on %s \"%s\"", 'wpdef' ),
				$this->get_user_display( get_current_user_id() ), $comment->comment_ID, $comment->comment_author,
				$post_type_label, $post->post_title );
		}

		return array( $text, $post_type_label );
	}

	/**
	 * @return bool|string
	 */
	public function process_generic_comment() {
		$args       = func_get_args();
		$hookname   = $args[0];
		$comment_id = $args[1]['comment_ID'];
		if ( ! isset( $args[1]['commentdata'] ) ) {
			$comment = get_comment( $comment_id );
			$comment = $comment->to_array();
		} else {
			$comment = $args[1]['commentdata'];
		}
		if ( ! isset( $args[1]['comment_approved'] ) ) {
			$comment_approved = '';
		} else {
			$comment_approved = $args[1]['comment_approved'];
		}
		$post            = get_post( $comment['comment_post_ID'] );
		$post_type       = get_post_type_object( $post->post_type );
		$post_type_label = strtolower( $post_type->labels->singular_name );
		switch ( $hookname ) {
			case 'comment_post':
				if ( $comment_approved === 'spam' ) {
					$comment_status = 'spam';
				} elseif ( $comment_approved === 1 ) {
					$comment_status = __( "approved", 'wpdef' );
				} else {
					$comment_status = __( "pending approval", 'wpdef' );
				}
				if ( $comment['comment_parent'] == 0 ) {
					$text = sprintf( __( "%s commented on %s \"%s\" - comment status: %s", 'wpdef' ),
						$comment['comment_author'], $post_type_label, $post->post_title, $comment_status );
				} else {
					$parent_comment = get_comment( $comment['comment_parent'] );
					$text           = sprintf( __( "%s replied to %s's comment on %s \"%s\" - comment status: %s",
						'wpdef' ),
						$comment['comment_author'], $parent_comment->comment_author, $post_type_label,
						$post->post_title, $comment_status );
				}
				break;
			case 'wp_insert_comment':
				if ( $comment_approved === 'spam' ) {
					$comment_status = 'spam';
				} elseif ( $comment_approved === 1 ) {
					$comment_status = __( "approved", 'wpdef' );
				} else {
					$comment_status = __( "pending approval", 'wpdef' );
				}
				if ( $comment['comment_parent'] == 0 ) {
					$text = sprintf( __( "%s commented on %s \"%s\" - comment status: %s", 'wpdef' ),
						$comment['comment_author'], $post_type_label, $post->post_title, $comment_status );
				} else {
					$parent_comment = get_comment( $comment['comment_parent'] );
					$text           = sprintf( __( "%s replied to %s's comment on %s \"%s\" - comment status: %s",
						'wpdef' ),
						$comment['comment_author'], $parent_comment->comment_author, $post_type_label,
						$post->post_title, $comment_status );
				}
				break;
			case 'deleted_comment':
				$text = sprintf( __( "%s deleted comment ID %s, comment author: %s on %s \"%s\"", 'wpdef' ),
					$this->get_user_display( get_current_user_id() ), $comment_id, $comment['comment_author'],
					$post_type_label, $post->post_title );
				break;
			case 'trash_comment':
				$text = sprintf( __( "%s trashed comment ID %s, comment author: %s on %s \"%s\"", 'wpdef' ),
					$this->get_user_display( get_current_user_id() ), $comment_id, $comment['comment_author'],
					$post_type_label, $post->post_title );
				break;
			case 'untrash_comment':
				$text = sprintf( __( "%s untrashed comment ID %s, comment author: %s on %s \"%s\"", 'wpdef' ),
					$this->get_user_display( get_current_user_id() ), $comment_id, $comment['comment_author'],
					$post_type_label, $post->post_title );
				break;
			case 'spam_comment':
				$text = sprintf( __( "%s marked comment ID %s, comment author: %s on %s \"%s\" as spam", 'wpdef' ),
					$this->get_user_display( get_current_user_id() ), $comment_id, $comment['comment_author'],
					$post_type_label, $post->post_title );
				break;
			case 'unspam_comment':
				$text = sprintf( __( "%s unmarked comment ID %s, comment author: %s on %s \"%s\" as spam", 'wpdef' ),
					$this->get_user_display( get_current_user_id() ), $comment_id, $comment['comment_author'],
					$post_type_label, $post->post_title );
				break;
			case 'edit_comment':
				$text = sprintf( __( "%s edited comment ID %s, comment author: %s on %s \"%s\"", 'wpdef' ),
					$this->get_user_display( get_current_user_id() ), $comment_id, $comment['comment_author'],
					$post_type_label, $post->post_title );
				break;
			default:
				return false;
		}

		return array( $text, $post_type_label );
	}
}