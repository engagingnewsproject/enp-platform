<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Audit;

use Calotes\Helper\Array_Cache;
use WP_Defender\Traits\User;
use WP_Defender\Model\Audit_Log;

class Post_Audit extends Audit_Event {
	use User;

	private $excluded_posttype = array(
		'wdscan_result',
		'wdf_scan',
		'wd_iplockout_log',
		'wd_ip_lockout',
		'wdf_scan_item',
	);

	/**
	 * We will add a hook, for updated event, and cache that event content.
	 * Later we weill use the hook save post, to determine this is insert new post or update the cache will be
	 * the array of various post, as we don't want data be excluded. This way we can get more control.
	 */
	public function __construct() {
		add_action( 'post_updated', array( &$this, 'cache_post_updated' ), 10, 3 );
	}

	public function cache_post_updated( $post_id, $after, $before ) {
		Array_Cache::append(
			'post_updated',
			array(
				'post_id' => $post_id,
				'after'   => $after,
				'before'  => $before,
			),
			'audit'
		);
	}

	/**
	 * @return array
	 */
	public function get_hooks() {
		return array(
			'save_post'              => array(
				'args'        => array( 'post_ID', 'post', 'is_updated' ),
				'callback'    => array( self::class, 'post_updated_callback' ),
				'event_type'  => Audit_Log::EVENT_TYPE_CONTENT,
				'action_type' => self::ACTION_UPDATED,
			),
			'transition_post_status' => array(
				'args'         => array( 'new_status', 'old_status', 'post' ),
				'event_type'   => Audit_Log::EVENT_TYPE_CONTENT,
				'action_type'  => self::ACTION_UPDATED,
				'false_when'   => array(
					array(
						'{{post->post_status}}',
						array(
							'inherit',
							'new',
							'auto-draft',
							'trash',
						),
					),
					array(
						'{{post->post_type}}',
						array_merge( array( 'revision', 'nav_menu_item' ), $this->excluded_posttype ),
					),
					array(
						'{{new_status}}',
						'{{old_status}}',
					),
					array(
						'{{old_status}}',
						array(
							'trash',
							'new',
						),
					),
				),
				'text'         => array(
					array(
						sprintf(
						/* translators: */
							__( '%1$s %2$s published %3$s "%4$s"', 'wpdef' ),
							'{{blog_name}}',
							'{{wp_user}}',
							'{{post_type_label}}',
							'{{post_title}}'
						),
						'{{new_status}}',
						'publish',
						'==',
					),
					array(
						sprintf(
						/* translators: */
							__( '%1$s %2$s pending %3$s "%4$s"', 'wpdef' ),
							'{{blog_name}}',
							'{{wp_user}}',
							'{{post_type_label}}',
							'{{post_title}}'
						),
						'{{new_status}}',
						'pending',
						'==',
					),
					array(
						sprintf(
						/* translators: */
							__( '%1$s %2$s drafted %3$s "%4$s"', 'wpdef' ),
							'{{blog_name}}',
							'{{wp_user}}',
							'{{post_type_label}}',
							'{{post_title}}'
						),
						'{{new_status}}',
						'draft',
						'==',
					),
					array(
						sprintf(
						/* translators: */
							__( '%1$s %2$s changed %3$s "%4$s" status from %5$s to %6$s', 'wpdef' ),
							'{{blog_name}}',
							'{{wp_user}}',
							'{{post_type_label}}',
							'{{post_title}}',
							'{{old_status}}',
							'{{new_status}}'
						),
						'{{new_status}}',
						'{{new_status}}',
						'==',
					),
				),
				'program_args' => array(
					'post_type_label' => array(
						'callable'        => 'get_post_type_object',
						'params'          => array(
							'{{post->post_type}}',
						),
						'result_property' => 'labels->singular_name',
					),
				),
				'custom_args'  => array(
					'post_title' => '{{post->post_title}}',
				),
				'context'      => '{{post_type_label}}',
			),
			'delete_post'            => array(
				'args'         => array( 'post_ID' ),
				'event_type'   => Audit_Log::EVENT_TYPE_CONTENT,
				'action_type'  => self::ACTION_DELETED,
				'text'         => sprintf(
				/* translators: */
					__( '%1$s %2$s deleted %3$s "%4$s"', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{post_type_label}}',
					'{{post_title}}'
				),
				'program_args' => array(
					'post'            => array(
						'callable' => 'get_post',
						'params'   => array(
							'{{post_ID}}',
						),
					),
					'post_type_label' => array(
						'callable'        => 'get_post_type_object',
						'params'          => array(
							'{{post->post_type}}',
						),
						'result_property' => 'labels->singular_name',
					),
					'post_title'      => array(
						'callable'        => 'get_post',
						'params'          => array(
							'{{post_ID}}',
						),
						'result_property' => 'post_title',
					),
				),
				'context'      => '{{post_type_label}}',
				'false_when'   => array(
					array(
						'{{post->post_type}}',
						array_merge(
							array(
								'revision',
								'attachment',
								'nav_menu_item',
							),
							$this->excluded_posttype
						),
					),
					array(
						'{{post_type_label}}',
						'',
					),
				),
			),
			'untrashed_post'         => array(
				'args'         => array( 'post_ID' ),
				'action_type'  => self::ACTION_RESTORED,
				'event_type'   => Audit_Log::EVENT_TYPE_CONTENT,
				'text'         => sprintf(
				/* translators: */
					__( '%1$s %2$s untrashed %3$s "%4$s"', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{post_type_label}}',
					'{{post_title}}'
				),
				'program_args' => array(
					'post'            => array(
						'callable' => 'get_post',
						'params'   => array(
							'{{post_ID}}',
						),
					),
					'post_type_label' => array(
						'callable'        => 'get_post_type_object',
						'params'          => array(
							'{{post->post_type}}',
						),
						'result_property' => 'labels->singular_name',
					),
					'post_title'      => array(
						'callable'        => 'get_post',
						'params'          => array(
							'{{post_ID}}',
						),
						'result_property' => 'post_title',
					),
				),
				'context'      => '{{post_type_label}}',
			),
			'trashed_post'           => array(
				'args'         => array( 'post_ID' ),
				'action_type'  => self::ACTION_TRASHED,
				'event_type'   => Audit_Log::EVENT_TYPE_CONTENT,
				'text'         => sprintf(
				/* translators: */
					__( '%1$s %2$s trashed %3$s "%4$s"', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{post_type_label}}',
					'{{post_title}}'
				),
				'program_args' => array(
					'post'            => array(
						'callable' => 'get_post',
						'params'   => array(
							'{{post_ID}}',
						),
					),
					'post_type_label' => array(
						'callable'        => 'get_post_type_object',
						'params'          => array(
							'{{post->post_type}}',
						),
						'result_property' => 'labels->singular_name',
					),
					'post_title'      => array(
						'callable'        => 'get_post',
						'params'          => array(
							'{{post_ID}}',
						),
						'result_property' => 'post_title',
					),
				),
				'context'      => '{{post_type_label}}',
			),
		);
	}

	public function post_updated_callback() {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		$args     = func_get_args();
		$hookname = $args[0];
		$post     = $args[1]['post'];

		if ( in_array(
			$post->post_status,
			array(
				'trash',
				'auto-draft',
			),
			true
		) || in_array( $post->post_type, array( 'revision' ), true )
		) {
			// Usually, wp wll append :trash to the post name, so this case we just return.
			return false;
		}

		if ( in_array( $post->post_type, $this->excluded_posttype, true ) ) {
			return false;
		}

		if ( 'nav_menu_item' === $post->post_type ) {
			return false;
		}

		$post_type = get_post_type_object( $post->post_type );
		if ( ! is_object( $post_type ) ) {
			return false;
		}

		$is_updated  = $args[1]['is_updated'];
		$post_before = null;
		$cached      = Array_Cache::get( 'post_updated', 'audit', array() );
		foreach ( $cached as $post_arr ) {
			if ( $post->ID === $post_arr['post_id'] ) {
				$post_before = $post_arr['before'];
				break;
			}
		}

		$blog_name = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		if ( true === $is_updated ) {
			if ( ! is_null( $post_before ) ) {
				$post_after  = $post->to_array();
				$post_before = $post_before->to_array();

				// Status transitions are handled via other hooks.
				if ( $post_before['post_status'] !== $post_after['post_status'] ) {
					return false;
				}

				// Unset the date modified, and post status, as we got another hook for that.
				unset( $post_after['post_modified'] );
				unset( $post_after['post_modified_gmt'] );
				unset( $post_after['post_status'] );
				unset( $post_before['post_modified'] );
				unset( $post_before['post_modified_gmt'] );
				unset( $post_before['post_status'] );
				if ( serialize( $post_before ) !== serialize( $post_after ) ) {
					$item_changed_count = count( array_diff_assoc( $post_before, $post_after ) );
					if ( $post_before['post_title'] !== $post_after['post_title'] && 1 === $item_changed_count ) {
						$text = sprintf(
							/* translators: */
							__( '%1$s %2$s updated %3$s ID %4$d, title from "%5$s" to "%6$s"', 'wpdef' ),
							$blog_name,
							$this->get_user_display( get_current_user_id() ),
							$post_type->labels->singular_name,
							$post_after['ID'],
							$post_before['post_title'],
							$post_after['post_title']
						);
					} elseif ( $post_before['post_name'] !== $post_after['post_name'] && 1 === $item_changed_count ) {
						$text = sprintf(
							/* translators: */
							__( '%1$s %2$s updated %3$s ID %4$d, slug from "%5$s" to "%6$s"', 'wpdef' ),
							$blog_name,
							$this->get_user_display( get_current_user_id() ),
							$post_type->labels->singular_name,
							$post_after['ID'],
							$post_before['post_name'],
							$post_after['post_name']
						);
					} elseif ( $post_before['post_author'] !== $post_after['post_author'] && 1 === $item_changed_count ) {
						$text = sprintf(
							/* translators: */
							__( '%1$s %2$s updated %3$s ID %4$d, author from "%5$s" to "%6$s"', 'wpdef' ),
							$blog_name,
							$this->get_user_display( get_current_user_id() ),
							$post_type->labels->singular_name,
							$post_after['ID'],
							$this->get_user_display( $post_before['post_author'] ),
							$this->get_user_display( $post_after['post_author'] )
						);
					} else {
						$text = sprintf(
							/* translators: */
							__( '%1$s %2$s updated %3$s "%4$s"', 'wpdef' ),
							$blog_name,
							$this->get_user_display( get_current_user_id() ),
							$post_type->labels->singular_name,
							$post_after['post_title']
						);
					}

					return array(
						$text,
						$post_type->labels->singular_name,
					);
				}
			}
		} else {
			if ( is_null( $post_before ) ) {
				$text = sprintf(
				/* translators: */
					__( '%1$s %2$s added new %3$s "%4$s"', 'wpdef' ),
					$blog_name,
					$this->get_user_display( get_current_user_id() ),
					$post_type->labels->singular_name,
					$post->post_title
				);

				return array(
					$text,
					$post_type->labels->singular_name,
				);
			}
		}

		return false;
	}

	public static function compare_post( $a, $b ) {
		if ( ! is_array( $a ) && ! is_array( $b ) ) {
			return strcmp( $a, $b ) === 0;
		} elseif ( is_array( $a ) && is_array( $b ) ) {
			return count( array_diff( $a, $b ) ) === 0;
		} else {
			return 0;
		}
	}

	public function dictionary() {
		return array();
	}
}
