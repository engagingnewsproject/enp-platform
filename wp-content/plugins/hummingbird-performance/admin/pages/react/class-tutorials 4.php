<?php
/**
 * React based Tutorials page.
 *
 * @since 2.7.3
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
 * Class Tutorials extends Page.
 */
class Tutorials extends Page {

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
	 * Renders the template header.
	 */
	protected function render_header() {
		?>
		<div class="sui-header">
			<h1 class="sui-header-title"><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<div class="sui-actions-right">
				<?php if ( ! apply_filters( 'wpmudev_branding_hide_doc_link', false ) ) : ?>
					<a href="<?php echo esc_url( Utils::get_link( 'tutorials' ) ); ?>" target="_blank" class="sui-button sui-button-ghost">
						<span class="sui-icon-open-new-window" aria-hidden="true"></span>
						<?php esc_html_e( 'View All', 'wphb' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<div class="sui-floating-notices">
			<div role="alert" id="wphb-ajax-update-notice" class="sui-notice" aria-live="assertive"></div>
			<?php do_action( 'wphb_sui_floating_notices' ); ?>
		</div>
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

		wp_enqueue_script(
			'wphb-react-tutorials',
			WPHB_DIR_URL . 'admin/assets/js/wphb-react-tutorials.min.js',
			array( 'wp-i18n' ),
			WPHB_VERSION,
			true
		);

		wp_add_inline_script(
			'wphb-react-tutorials',
			'wp.i18n.setLocaleData( ' . wp_json_encode( Utils::get_locale_data() ) . ', "wphb" );',
			'before'
		);
	}

}
