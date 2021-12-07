<?php

namespace WP_Defender\Traits;

trait Setting {

	/**
	 * Prepare notice message.
	 *
	 * @param array  $data Form request.
	 * @param bool   $old_data Model activate value.
	 * @param string $module_name
	 *
	 * @return string
	 */
	public function get_update_message( $data, $old_data, $module_name ) {
		$new_data = (bool) $data['enabled'];

		// If old data and new data is matched, then it is not activated or deactivated.
		if ( $old_data === $new_data ) {
			return __( 'Your settings have been updated.', 'wpdef' );
		}

		if ( $new_data ) {
			/* translators: %s: Module name. */
			return sprintf( __( '%s has been activated.', 'wpdef' ), $module_name );
		}

		/* translators: %s: Module name. */
		return sprintf( __( '%s has been deactivated.', 'wpdef' ), $module_name );
	}
}