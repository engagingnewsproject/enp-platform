<?php
/**
 * Admin UI
 * Adds the WP Engine Admin settings.
 *
 * @package wpengine/common-mu-plugin
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Check user capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

$wpe_common = WpeCommon::instance();
$site_info  = $wpe_common->get_site_info();

// Nonce verification is being ignored here because no user action is being taken here, and no data is being saved. This simply loads the page in question.
// Ignoring the nonce allows this page to be loaded from a bookmark, for example.
// No saving logic should be added to this page. Rather, it should be handled in a separate function, or ideally through the REST api, which has nonce protection.
$page_var        = isset( $_REQUEST['page'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification
$active_tab      = isset( $_REQUEST['tab'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['tab'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification
$active_page_tab = ( 'wpengine-staging' === $page_var ) ? 'staging' : 'info';

$page_title = __( 'WP Engine has your back', 'wpe-common-plugin' );
$page_logo  = WPE_PLUGIN_URL . '/images/wpe-logo-white.svg';

// Override with whitelabel vaues if they exist.
if ( $wpe_common->is_whitelabel() ) {
	$page_title = get_option( 'wpe-install-menu_title', $page_title );
	$page_logo  = get_option( 'wpe-install-menu_icon', $page_logo );
}
?>

<div class="wrap">
	<div id="wpe-common-plugin-admin">
		<!-- Admin Header -->
		<div class="wpe-common-plugin-admin-header wpe-common-plugin-grid-2">
			<div class="wpe-common-plugin-header-title-area">
				<h1><img class="wpe-plugin-common-logo" src="<?php echo esc_url( $page_logo ); ?>" alt="" /><?php echo esc_html( $page_title ); ?></h1>
			</div>
			<div class="wpe-common-plugin-header-controls-area">
				<a href="https://my.wpengine.com/support/" class="wpe-header-button" target="_blank" rel="noopener noreferrer"><img class="wpe-header-button-icon" src="<?php echo esc_url( WPE_PLUGIN_URL . '/images/wpe-help.svg' ); ?>" alt="" /><?php esc_html_e( 'Visit Support Page', 'wpe-common-plugin' ); ?></a>
				<a href="https://my.wpengine.com/" class="wpe-header-button" target="_blank" rel="noopener noreferrer"><img class="wpe-header-button-icon" src="<?php echo esc_url( WPE_PLUGIN_URL . '/images/wpe-portal.svg' ); ?>" alt="" /><?php esc_html_e( 'Go to Portal', 'wpe-common-plugin' ); ?></a>
			</div>
		</div>
		<!-- Admin Nav -->
		<nav class="wpe-nav-tab-wrapper">
			<a href="?page=wpengine-common" class="wpe-nav-tab wpe-admin-button 
			<?php
			if ( '' === $active_tab && 'info' === $active_page_tab ) {
				echo 'wpe-nav-tab-active';
			}
			?>
			"><?php esc_html_e( 'Information', 'wpe-common-plugin' ); ?></a>
			<?php if ( wpe\plugin\Wpe_Cache_Adaptor::get_instance()->is_cache_plugin_present() ) { ?>
				<a href="?page=wpengine-common&tab=caching" class="wpe-nav-tab wpe-admin-button 
				<?php
				if ( 'caching' === $active_tab ) {
					echo 'wpe-nav-tab-active';
				}
				?>
				"><?php esc_html_e( 'Caching', 'wpe-common-plugin' ); ?></a>
			<?php } ?>
			<a href="?page=wpengine-common&tab=display" class="wpe-nav-tab wpe-admin-button 
			<?php
			if ( 'display' === $active_tab ) {
				echo 'wpe-nav-tab-active';
			}
			?>
			"><?php esc_html_e( 'Display', 'wpe-common-plugin' ); ?></a>
			<a href="?page=wpengine-common&tab=site-settings" class="wpe-nav-tab wpe-admin-button 
			<?php
			if ( 'site-settings' === $active_tab ) {
				echo 'wpe-nav-tab-active';
			}
			?>
			"><?php esc_html_e( 'Site Settings', 'wpe-common-plugin' ); ?></a>
			<?php if ( ! $wpe_common->is_legacy_staging_disabled() ) { ?>
				<a href="?page=wpengine-common&tab=staging" class="wpe-nav-tab wpe-admin-button 
				<?php
				if ( 'staging' === $active_tab ) {
					echo 'wpe-nav-tab-active';
				}
				?>
				"><?php esc_html_e( 'Legacy Staging', 'wpe-common-plugin' ); ?></a>
				<?php
			}
			?>
		</nav>
		<!-- Admin Body -->
		<div class="wpe-common-plugin-admin-body">
		<?php
		if ( '' === $active_tab && 'info' === $active_page_tab ) {
			require_once WPE_PLUGIN_DIR . '/views/admin/main/tabs/information/logic.php';
			do_action( 'wpe_common_admin_notices' );
			include WPE_PLUGIN_DIR . '/views/admin/main/tabs/information/view.php';
		} elseif ( 'caching' === $active_tab ) {
			do_action( 'wpe_common_admin_notices' );
			wpengine\cache_plugin\WpeCachePage::display_cache_page();
		} elseif ( 'display' === $active_tab ) {
			require_once WPE_PLUGIN_DIR . '/views/admin/main/tabs/display/logic.php';
			do_action( 'wpe_common_admin_notices' );
			include WPE_PLUGIN_DIR . '/views/admin/main/tabs/display/view.php';
		} elseif ( 'site-settings' === $active_tab ) {
			require_once WPE_PLUGIN_DIR . '/views/admin/main/tabs/site-settings/logic.php';
			do_action( 'wpe_common_admin_notices' );
			include WPE_PLUGIN_DIR . '/views/admin/main/tabs/site-settings/view.php';
		} elseif ( 'staging' === $active_tab ) {
			require_once WPE_PLUGIN_DIR . '/views/admin/main/tabs/staging/logic.php';
			do_action( 'wpe_common_admin_notices' );
			include WPE_PLUGIN_DIR . '/views/admin/main/tabs/staging/view.php';
		}
		?>
		</div>
	</div>
</div>
