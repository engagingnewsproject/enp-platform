<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Deactivate addon.
 *
 * @since 1.0.0
 */
function ctf_deactivate_addon() {
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/PluginSilentUpgrader.php';
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/PluginSilentUpgraderSkin.php';
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/class-install-skin.php';
	// Run a security check.
	check_ajax_referer( 'ctf-admin', 'nonce' );

	// Check for permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	$type = 'addon';
	if ( ! empty( $_POST['type'] ) ) {
		$type = sanitize_key( $_POST['type'] );
	}

	if ( isset( $_POST['plugin'] ) ) {
		deactivate_plugins( $_POST['plugin'] );

		if ( 'plugin' === $type ) {
			wp_send_json_success( esc_html__( 'Plugin deactivated.', 'custom-twitter-feeds' ) );
		} else {
			wp_send_json_success( esc_html__( 'Addon deactivated.', 'custom-twitter-feeds' ) );
		}
	}

	wp_send_json_error( esc_html__( 'Could not deactivate the addon. Please deactivate from the Plugins page.', 'custom-twitter-feeds' ) );
}
add_action( 'wp_ajax_ctf_deactivate_addon', 'ctf_deactivate_addon' );

/**
 * Activate addon.
 *
 * @since 1.0.0
 */
function ctf_activate_addon() {

	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/PluginSilentUpgrader.php';
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/PluginSilentUpgraderSkin.php';
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/class-install-skin.php';
	// Run a security check.
	check_ajax_referer( 'ctf-admin', 'nonce' );
	// Check for permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	if ( isset( $_POST['plugin'] ) ) {

		$type = 'addon';
		if ( ! empty( $_POST['type'] ) ) {
			$type = sanitize_key( $_POST['type'] );
		}

		$activate = activate_plugins( $_POST['plugin'] );

		if ( ! is_wp_error( $activate ) ) {
			if ( 'plugin' === $type ) {
				wp_send_json_success( esc_html__( 'Plugin activated.', 'custom-twitter-feeds' ) );
			} else {
				wp_send_json_success( esc_html__( 'Addon activated.', 'custom-twitter-feeds' ) );
			}
		}
	}

	wp_send_json_error( esc_html__( 'Could not activate addon. Please activate from the Plugins page.', 'custom-twitter-feeds' ) );
}
add_action( 'wp_ajax_ctf_activate_addon', 'ctf_activate_addon' );

/**
 * Install addon.
 *
 * @since 1.0.0
 */
function ctf_install_addon() {
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/PluginSilentUpgrader.php';
	require_once trailingslashit( CTF_PLUGIN_DIR ) . 'inc/Admin/PluginSilentUpgraderSkin.php';
	// Run a security check.
	check_ajax_referer( 'ctf-admin', 'nonce' );

	// Check for permissions.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error();
	}

	$error = esc_html__( 'Could not install addon. Please download from wpforms.com and install manually.', 'custom-twitter-feeds' );

	if ( empty( $_POST['plugin'] ) ) {
		wp_send_json_error( $error );
	}

	// Set the current screen to avoid undefined notices.
	set_current_screen( 'custom-twitter-feeds' );

	// Prepare variables.
	$url = esc_url_raw(
		add_query_arg(
			array(
				'page' => 'custom-twitter-feeds',
			),
			admin_url( 'admin.php' )
		)
	);

	$creds = request_filesystem_credentials( $url, '', false, false, null );

	// Check for file system permissions.
	if ( false === $creds ) {
		wp_send_json_error( $error );
	}

	if ( ! WP_Filesystem( $creds ) ) {
		wp_send_json_error( $error );
	}

	/*
	 * We do not need any extra credentials if we have gotten this far, so let's install the plugin.
	 */

	require_once CTF_PLUGIN_DIR . 'inc/Admin/class-install-skin.php';

	// Do not allow WordPress to search/download translations, as this will break JS output.
	remove_action( 'upgrader_process_complete', array( 'Language_Pack_Upgrader', 'async_upgrade' ), 20 );

	// Create the plugin upgrader with our custom skin.
	$installer = new CTF\Helpers\PluginSilentUpgrader( new CTF_Install_Skin() );

	// Error check.
	if ( ! method_exists( $installer, 'install' ) || empty( $_POST['plugin'] ) ) {
		wp_send_json_error( $error );
	}

	$installer->install( $_POST['plugin'] ); // phpcs:ignore

	// Flush the cache and return the newly installed plugin basename.
	wp_cache_flush();

	$plugin_basename = $installer->plugin_info();

	if ( $plugin_basename ) {

		$type = 'addon';
		if ( ! empty( $_POST['type'] ) ) {
			$type = sanitize_key( $_POST['type'] );
		}

		// Activate the plugin silently.
		$activated = activate_plugin( $plugin_basename );

		if ( ! is_wp_error( $activated ) ) {
			wp_send_json_success(
				array(
					'msg'          => 'plugin' === $type ? esc_html__( 'Plugin installed & activated.', 'custom-twitter-feeds' ) : esc_html__( 'Addon installed & activated.', 'custom-twitter-feeds' ),
					'is_activated' => true,
					'basename'     => $plugin_basename,
				)
			);
		} else {
			wp_send_json_success(
				array(
					'msg'          => 'plugin' === $type ? esc_html__( 'Plugin installed.', 'custom-twitter-feeds' ) : esc_html__( 'Addon installed.', 'custom-twitter-feeds' ),
					'is_activated' => false,
					'basename'     => $plugin_basename,
				)
			);
		}
	}

	wp_send_json_error( $error );
}
add_action( 'wp_ajax_ctf_install_addon', 'ctf_install_addon' );