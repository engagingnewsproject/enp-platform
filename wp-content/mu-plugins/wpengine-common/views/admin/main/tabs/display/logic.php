<?php
/**
 * Admin UI - Logic for actions from the Display tab.
 *
 * @package wpengine/common-mu-plugin
 */

declare(strict_types=1);

namespace wpengine\admin_options;

use WpeCommon;

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

/**
 * Regarding the WP Engine Quick Links and who is able to see/use it, this function ensures that the list of user
 * roles does not include the "administrator" role, because it is enabled for that role by default.
 */
function get_access_roles() {
	$roles = ( WP_Roles() )->get_names();
	unset( $roles['administrator'] );
	return $roles;
}

/**
 * Handles a form submission for the display options.
 */
function handle_display_options_submission() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( ! isset( $_POST['displayoptions'] ) ) {
		return;
	}

	check_admin_referer( PWP_NAME . '-config' );

	$wpe_plugin            = WpeCommon::instance();
	$enable_admin_bar_name = 'wpe-adminbar-enable';
	$admin_bar_roles_name  = 'wpe-adminbar-roles';

	$wpe_plugin->set_wpengine_admin_bar_enabled( ! empty( $_POST[ $enable_admin_bar_name ] ) );

	if ( ! empty( $_POST[ $admin_bar_roles_name ] ) && is_array( $_POST[ $admin_bar_roles_name ] ) ) {
		$wpe_plugin->set_option(
			$admin_bar_roles_name,
			array_map( 'sanitize_text_field', wp_unslash( $_POST[ $admin_bar_roles_name ] ) )
		);
	} else {
		delete_option( $admin_bar_roles_name );
	}

	add_action(
		'wpe_common_admin_notices',
		function() {
			?>
			<div class="notice wpe-success is-dismissable inline">
				<p><?php esc_html_e( 'Settings have been successfully updated', 'wpe-common' ); ?></p>
			</div>
			<?php
		}
	);
}

handle_display_options_submission();
