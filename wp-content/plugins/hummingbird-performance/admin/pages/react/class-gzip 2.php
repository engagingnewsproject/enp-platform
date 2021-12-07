<?php
/**
 * React based Gzip page.
 *
 * @since 2.2.0
 * @package Hummingbird\Admin\Pages\React
 */

namespace Hummingbird\Admin\Pages\React;

use Hummingbird\Admin\Page;
use Hummingbird\Core\Module_Server;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Gzip extends Page.
 */
class Gzip extends Page {

	/**
	 * Render the page.
	 */
	public function render() {
		$settings = Settings::get_settings( 'settings' );
		?>
		<div class="sui-wrap<?php echo $settings['accessible_colors'] ? ' sui-color-accessible ' : ' '; ?>wrap-wp-hummingbird wrap-wp-hummingbird-page <?php echo 'wrap-' . esc_attr( $this->slug ); ?>">
			<?php $this->render_header(); ?>
			<div class="row" id="<?php echo 'wrap-' . esc_attr( $this->slug ); ?>"></div>
			<?php $this->render_footer(); ?>
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

		wp_enqueue_style(
			'wphb-sui',
			WPHB_DIR_URL . 'admin/assets/css/wphb-react-gzip.min.css',
			array(),
			WPHB_VERSION
		);

		wp_enqueue_script(
			'wphb-react-gzip',
			WPHB_DIR_URL . 'admin/assets/js/wphb-react-gzip.min.js',
			array( 'wp-i18n', 'lodash' ),
			WPHB_VERSION,
			true
		);

		wp_localize_script(
			'wphb-react-gzip',
			'wphb',
			array(
				'isMember' => Utils::is_member(),
				'links'    => array(
					'modules' => array(
						'gzip' => Utils::get_admin_menu_url( 'gzip' ),
					),
					'support' => array(
						'chat'  => Utils::get_link( 'chat' ),
						'forum' => Utils::get_link( 'support' ),
					),
				),
				'nonces'   => array(
					'HBFetchNonce' => wp_create_nonce( 'wphb-fetch' ),
				),
				'module'   => array(
					'is_wpmu_hosting'   => isset( $_SERVER['WPMUDEV_HOSTED'] ),
					'is_white_labeled'  => apply_filters( 'wpmudev_branding_hide_branding', false ),
					'htaccess_error'    => isset( $_GET['htaccess-error'] ), // Input data ok.
					'htaccess_writable' => Module_Server::is_htaccess_writable(),
					'htaccess_written'  => Module_Server::is_htaccess_written( 'gzip' ),
					'servers_array'     => Module_Server::get_servers(),
					'server_name'       => Module_Server::get_server_type(),
					'snippets'          => array(
						'apache' => Module_Server::get_code_snippet( 'gzip', 'apache' ),
						'iis'    => Module_Server::get_code_snippet( 'gzip', 'iis' ),
						'nginx'  => Module_Server::get_code_snippet( 'gzip', 'nginx' ),
					),
				),
			)
		);

		wp_add_inline_script(
			'wphb-react-gzip',
			'wp.i18n.setLocaleData( ' . wp_json_encode( Utils::get_locale_data() ) . ', "wphb" );',
			'before'
		);
	}

}
