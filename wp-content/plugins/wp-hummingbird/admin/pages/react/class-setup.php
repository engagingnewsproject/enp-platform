<?php
/**
 * React based Setup Wizard page.
 *
 * @since 3.3.1
 * @package Hummingbird\Admin\Pages\React
 */

namespace Hummingbird\Admin\Pages\React;

use Hummingbird\Admin\Page;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Setup extends Page.
 */
class Setup extends Page {

	/**
	 * Render the page.
	 */
	public function render() {
		$settings = Settings::get_settings( 'settings' );
		?>
		<div class="sui-wrap<?php echo $settings['accessible_colors'] ? ' sui-color-accessible ' : ' '; ?>wrap-wp-hummingbird wrap-wp-hummingbird-page <?php echo 'wrap-' . esc_attr( $this->slug ); ?>">
			<div id="wrap-wphb-setup"></div>
		</div><!-- end container -->
		<?php
	}

	/**
	 * Enqueue scripts.
	 *
	 * @param string $hook  Hook from where the call is made.
	 */
	public function enqueue_scripts( $hook ) {
		parent::enqueue_scripts( $hook );

		// We don't need the scripts/styles from non-React pages.
		wp_dequeue_script( 'wphb-admin' );
		wp_dequeue_style( 'wphb-admin' );

		remove_action( 'admin_footer', array( Utils::admin(), 'maybe_check_files' ) );

		wp_enqueue_style( 'wphb-sui', WPHB_DIR_URL . 'admin/assets/css/wphb-react-gzip.min.css', array(), WPHB_VERSION );
		wp_enqueue_style( 'wphb-setup-wizard', WPHB_DIR_URL . 'admin/assets/css/wphb-setup-wizard.min.css', array(), WPHB_VERSION );
		wp_enqueue_script( 'wphb-setup-wizard', WPHB_DIR_URL . 'admin/assets/js/wphb-setup-wizard.min.js', array( 'wp-i18n', 'lodash' ), WPHB_VERSION, true );

		$run_url = add_query_arg( 'run', 'true', Utils::get_admin_menu_url( 'performance' ) );
		$run_url = wp_nonce_url( $run_url, 'wphb-run-performance-test' );
		$run_url = str_replace( '&amp;', '&', $run_url );

		$args = array(
			'isMember'       => Utils::is_member(),
			'isNetworkAdmin' => is_network_admin(),
			'hasUptime'      => Utils::get_module( 'uptime' )->has_access(),
			'links'          => array(
				'configs'    => Utils::get_admin_menu_url( 'settings' ) . '&view=configs',
				'wphbDirUrl' => WPHB_DIR_URL,
				'plugins'    => network_admin_url( 'plugins.php' ),
				'docs'       => Utils::get_link( 'docs', 'onboarding' ),
				'upsell'     => Utils::get_link( 'plugin', 'onboarding' ),
				'pluginDash' => Utils::get_admin_menu_url(),
				'tracking'   => Utils::get_link( 'tracking', 'onboarding' ),
				'runPerf'    => $run_url,
			),
			'hasWoo'         => class_exists( 'woocommerce' ),
			'minifySteps'    => Utils::get_module( 'minify' )->scanner->get_scan_steps(),
			'nonces'         => array(
				'HBFetchNonce' => wp_create_nonce( 'wphb-fetch' ),
			),
		);

		$args = array_merge_recursive( $args, Utils::get_tracking_data() );

		wp_localize_script( 'wphb-setup-wizard', 'wphb', $args );
		wp_add_inline_script(
			'wphb-setup-wizard',
			'wp.i18n.setLocaleData( ' . wp_json_encode( Utils::get_locale_data() ) . ', "wphb" );',
			'before'
		);
	}

}