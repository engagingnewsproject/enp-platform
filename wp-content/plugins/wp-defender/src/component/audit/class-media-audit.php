<?php
/**
 * Handles media-related audit events in WordPress.
 *
 * @package WP_Defender\Component\Audit
 */

namespace WP_Defender\Component\Audit;

use WP_Defender\Traits\User;
use WP_Defender\Model\Audit_Log;

/**
 * Handle media-related audit events such as uploading, updating, and deleting media files.
 */
class Media_Audit extends Audit_Event {

	use User;

	/**
	 * Constant for the 'uploaded' action type.
	 */
	public const ACTION_UPLOADED = 'uploaded';

	/**
	 * Returns an array of hooks associated with media actions.
	 * This method maps WordPress hooks to their respective event types, action types, and other parameters needed for
	 * logging.
	 *
	 * @return array An associative array where keys are WordPress hooks and values are arrays detailing the hook
	 *     handling.
	 */
	public function get_hooks(): array {
		return array(
			'add_attachment'     => array(
				'args'         => array( 'post_ID' ),
				'event_type'   => Audit_Log::EVENT_TYPE_MEDIA,
				'action_type'  => self::ACTION_UPLOADED,
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: File path */
					esc_html__( '%1$s %2$s uploaded a file: "%3$s" to Media Library', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{file_path}}'
				),
				'program_args' => array(
					'file_path' => array(
						'callable' => 'get_post_meta',
						'params'   => array(
							'{{post_ID}}',
							'_wp_attached_file',
							true,
						),
					),
					'mime_type' => array(
						'callable' => array( self::class, 'get_mime_type' ),
						'params'   => array(
							'{{post_ID}}',
						),
					),
				),
				'context'      => '{{mime_type}}',
			),
			'attachment_updated' => array(
				'args'         => array( 'post_ID' ),
				'action_type'  => self::ACTION_UPDATED,
				'event_type'   => Audit_Log::EVENT_TYPE_MEDIA,
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: File path */
					esc_html__( '%1$s %2$s updated a file: "%3$s" from Media Library', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{file_path}}'
				),
				'program_args' => array(
					'file_path' => array(
						'callable' => 'get_post_meta',
						'params'   => array(
							'{{post_ID}}',
							'_wp_attached_file',
							true,
						),
					),
					'mime_type' => array(
						'callable' => array( self::class, 'get_mime_type' ),
						'params'   => array(
							'{{post_ID}}',
						),
					),
				),
				'context'      => '{{mime_type}}',
			),
			'delete_attachment'  => array(
				'args'         => array( 'post_ID' ),
				'action_type'  => self::ACTION_DELETED,
				'event_type'   => Audit_Log::EVENT_TYPE_MEDIA,
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: File path */
					esc_html__( '%1$s %2$s deleted a file: "%3$s" from Media Library', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{file_path}}'
				),
				'program_args' => array(
					'file_path' => array(
						'callable' => 'get_post_meta',
						'params'   => array(
							'{{post_ID}}',
							'_wp_attached_file',
							true,
						),
					),
					'mime_type' => array(
						'callable' => array( self::class, 'get_mime_type' ),
						'params'   => array(
							'{{post_ID}}',
						),
					),
				),
				'context'      => '{{mime_type}}',
			),
		);
	}

	/**
	 * Provides a dictionary for translating action types into human-readable formats.
	 * This method is used to provide localized strings for action types.
	 *
	 * @return array An associative array where keys are action types and values are their localized strings.
	 */
	public function dictionary(): array {
		return array(
			self::ACTION_UPLOADED => esc_html__( 'Uploaded', 'wpdef' ),
		);
	}

	/**
	 * Retrieves the MIME type of file based on its post ID.
	 * This method uses the WordPress function get_post_meta to fetch the file path from post meta and then determines
	 * the MIME type.
	 *
	 * @param  int $post_ID  The ID of the post to retrieve the MIME type for.
	 *
	 * @return string The MIME type of the file.
	 */
	public function get_mime_type( $post_ID ) {
		$file_path = get_post_meta( $post_ID, '_wp_attached_file', true );

		return pathinfo( $file_path, PATHINFO_EXTENSION );
	}
}