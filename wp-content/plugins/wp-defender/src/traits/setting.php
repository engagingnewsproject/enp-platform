<?php
/**
 * Helper functions for setting related tasks.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

trait Setting {

	/**
	 * Prepare notice message.
	 *
	 * @param  array  $data  Form request.
	 * @param  bool   $old_data  Model activate value.
	 * @param  string $module_name  Module name.
	 *
	 * @return string
	 */
	public function get_update_message( $data, $old_data, $module_name ) {
		// Empty data after sanitizing.
		if ( empty( $data ) ) {
			return esc_html__( 'Your settings have been updated.', 'wpdef' );
		}
		$new_data = (bool) $data['enabled'];
		// If old data and new data is matched, then it is not activated or deactivated.
		if ( $old_data === $new_data ) {
			return esc_html__( 'Your settings have been updated.', 'wpdef' );
		}

		if ( $new_data ) {
			/* translators: %s: Module name. */
			return sprintf( esc_html__( '%s has been activated.', 'wpdef' ), $module_name );
		}

		/* translators: %s: Module name. */

		return sprintf( esc_html__( '%s has been deactivated.', 'wpdef' ), $module_name );
	}
}