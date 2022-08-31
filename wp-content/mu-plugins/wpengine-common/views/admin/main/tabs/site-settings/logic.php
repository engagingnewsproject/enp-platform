<?php
/**
 * Admin UI - Site Settings Logic
 *
 * @package wpengine/common-mu-plugin
 */

declare(strict_types=1);

namespace wpengine\admin_options;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Check user capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

/**
 * Handles displaying an error notice.
 *
 * @param string $type The type of notice to be shown.
 * @param string $message The message that should be included.
 */
function show_admin_notice( string $type, string $message ) {
	if ( ! in_array( $type, array( 'default', 'error', 'info', 'success', 'warning' ), true ) ) {
		return;
	}
	$type = esc_attr( "wpe-{$type}" );
	echo wp_kses_post( "<div class=\"notice {$type} is-dismissible inline\"><p>{$message}</p></div>" );
}

/**
 * Handles the File Reset request.
 */
function handle_file_reset_request() {
	if ( ! wpe_param( 'file-perms' ) ) {
		return;
	}
	check_admin_referer( PWP_NAME . '-site-settings-file-perm-reset' );

	$api_domain = wpe_el( $GLOBALS, 'api-domain', 'https://api.wpengine.com' );
	$url        = "${api_domain}/1.2/?method=file-permissions&account_name=" . PWP_NAME . '&wpe_apikey=' . WPE_APIKEY;
	$http       = new \WP_Http();
	$resp       = $http->get( $url );

	if (
		empty( $resp['response'] ) ||
		200 !== $resp['response']['code'] ||
		is_a( $resp, 'WP_Error' )
	) {
		add_action(
			'wpe_common_admin_notices',
			function() {
				show_admin_notice( 'error', __( 'Failed to reset file permissions, please try again.', 'wpe-common' ) );
			}
		);
	}

	$data = json_decode( $resp['body'], true );

	if ( $data['success'] ) {
		add_action(
			'wpe_common_admin_notices',
			function() use ( $data ) {
				show_admin_notice( 'success', $data['message'] );
			}
		);
	}
}

/**
 * Enables ORDER BY RAND() setting.
 *
 * @uses \WpeCommon::is_rand_enabled
 * @uses \WpeCommon::set_rand_enabled
 *
 * @param \WpeCommon  $wpe_common Instance of the WpeCommon class.
 * @param string|bool $enable_rand New value to be set.
 */
function enable_rand( \WpeCommon $wpe_common, $enable_rand ): void {
	$current_state = filter_var( $wpe_common->is_rand_enabled(), FILTER_VALIDATE_BOOLEAN );
	$new_state     = filter_var( $enable_rand, FILTER_VALIDATE_BOOLEAN );

	if ( $current_state !== $new_state ) {
		$wpe_common->set_rand_enabled( $new_state );

		$message = sprintf(
			'%1$s <b>%2$s</b>',
			__( 'ORDER BY RAND() support is now', 'wpe-common' ),
			$new_state ? 'enabled' : 'disabled'
		);

		add_action(
			'wpe_common_admin_notices',
			function() use ( $message ) {
				show_admin_notice( 'success', $message );
			}
		);
	}
}

/**
 * Sets the HTML Post Process Regex.
 *
 * @uses \WpeCommon::get_regex_html_post_process_text
 * @uses \WpeCommon::set_regex_html_post_process_text
 *
 * @param \WpeCommon $wpe_common Instance of the WpeCommon class.
 * @param string     $regex_value Post Processing Regex value to save.
 */
function set_post_process_regex( \WpeCommon $wpe_common, string $regex_value ): void {
	$regex_value = trim( $regex_value );
	$old_regex   = trim( $wpe_common->get_regex_html_post_process_text() );

	if ( $old_regex === $regex_value ) {
		return;
	}

	$result = $wpe_common->set_regex_html_post_process_text( $regex_value );

	if ( true !== $result ) {
		$message = sprintf(
			'<b>%1$s</b><br>%2$s<br>%3$s',
			__( 'Error in HTML replacement regex', 'wpe-common' ),
			$result,
			__( '(Maybe you forgot the beginning and ending characters?)', 'wpe-common' )
		);
		add_action(
			'wpe_common_admin_notices',
			function() use ( $message ) {
				show_admin_notice( 'error', $message );
			}
		);
	} else {
		add_action(
			'wpe_common_admin_notices',
			function() {
				show_admin_notice(
					'success',
					__( 'HTML Post-Processing saved.', 'wpe-common' )
				);
			}
		);
	}
}

/**
 * Handles the Advanced Options actions.
 *
 * @note The phpcs Detected usage of a non-sanitized input variable error
 *       is intentionally supressed. Too much sanitization of the regex input
 *       will remove essential regex characters.
 *
 * @note Sanitization and Validation of the inbound regex
 *       is handled by WpeCommon::set_regex_html_post_process_text
 *
 * @see https://github.com/wpengine/wpengine-common-mu-plugin/blob/master/src/wpengine-common/plugin.php#L1443
 */
function handle_advanced_options_request(): void {
	if ( ! wpe_param( 'advanced' ) ) {
		return;
	}
	check_admin_referer( PWP_NAME . '-site-settings-advanced-options' );

	$wpe_common         = \WpeCommon::instance();
	$rand_enabled       = isset( $_POST['rand_enabled'] ) ? true : false;
	$post_process_regex = isset( $_POST['post_process_regex'] ) ? wp_unslash( $_POST['post_process_regex'] ) : ''; //phpcs:ignore

	enable_rand( $wpe_common, $rand_enabled );
	set_post_process_regex( $wpe_common, $post_process_regex );
}

handle_file_reset_request();
handle_advanced_options_request();
