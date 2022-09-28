<?php

namespace wpengine\sign_on_plugin;

require_once __DIR__ . '/security-checks.php';
\wpengine\sign_on_plugin\check_security();

class Logger {

	const INSTALL_NAME_ERROR               = 'error_on_invalid_install_name';
	const NONCE_META_DATA_VALIDATION_ERROR = 'error_on_nonce_meta_data_validation';
	const MULTISITE_ENABLED_ERROR          = 'error_multisite_enabled';
	const GENERAL_EXCEPTION_ERROR          = 'error_general_exception';
	const ADD_USER_META_ERROR              = 'error_on_add_user_meta';
	const USER_CREATE_ERROR                = 'error_on_user_create';
	const IMPERSONATED_USER_ERROR          = 'error_on_user_impersonation';
	const NO_REFERER_ERROR                 = 'error_user_login_check_no_referer_given';
	const WP_JSON_REFERER_ERROR            = 'error_wp_json_referer';
	const NO_REFERAL_ID_ERROR              = 'error_empty_request_id';
	const INVALID_REFERER_ERROR            = 'error_invalid_referer';

	const USER_LOGGED_IN     = 'info_user_logged_in';
	const USER_NOT_LOGGED_IN = 'info_user_not_logged_in';

	public static function log( $event, $data, $user_email = null, $install_name = null ) {
		try {
			$output_data = wp_json_encode( $data );
		} catch ( \Exception $e ) {
			// phpcs:ignore
			$output_data = print_r( $data, true );
		}

		$user_email_and_install = self::build_user_email_and_install_string( $user_email, $install_name );

		// phpcs:ignore
		error_log( "wpeseamlessloginplugin:event=$event $user_email_and_install" . $output_data );
	}

	private static function build_user_email_and_install_string( $user_email, $install_name ) {
		$user_email_and_install  = isset( $user_email ) ? "user_email=$user_email " : '';
		$user_email_and_install .= isset( $install_name ) ? "install_name=$install_name " : '';
		return $user_email_and_install;
	}
}
