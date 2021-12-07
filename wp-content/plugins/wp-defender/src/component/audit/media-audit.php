<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component\Audit;

use WP_Defender\Traits\User;
use WP_Defender\Model\Audit_Log;

class Media_Audit extends Audit_Event {
	use User;

	const ACTION_UPLOADED = 'uploaded';

	public function get_hooks() {

		return array(
			'add_attachment'     => array(
				'args'         => array( 'post_ID' ),
				'event_type'   => Audit_Log::EVENT_TYPE_MEDIA,
				'action_type'  => self::ACTION_UPLOADED,
				'text'         => sprintf(
				/* translators: */
					__( '%1$s %2$s uploaded a file: "%3$s" to Media Library', 'wpdef' ),
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
				/* translators: */
					__( '%1$s %2$s updated a file: "%3$s" from Media Library', 'wpdef' ),
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
				/* translators: */
					__( '%1$s %2$s deleted a file: "%3$s" from Media Library', 'wpdef' ),
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

	public function dictionary() {

		return array(
			self::ACTION_UPLOADED => esc_html__( 'Uploaded', 'wpdef' ),
		);
	}

	public function get_mime_type( $post_ID ) {
		$file_path = get_post_meta( $post_ID, '_wp_attached_file', true );

		return pathinfo( $file_path, PATHINFO_EXTENSION );
	}
}