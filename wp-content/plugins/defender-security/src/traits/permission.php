<?php

namespace WP_Defender\Traits;

trait Permission {

	/**
	 * Check if the current user have permission for execute an action.
	 *
	 * @return bool
	 */
	public function check_permission() {
		if ( ! is_user_logged_in() ) {
			return false;
		}
		$cap = is_multisite() ? 'manage_network_options' : 'manage_options';

		return current_user_can( $cap );
	}
}
