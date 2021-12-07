<?php
/**
 * Hummingbird PRO upgrade page.
 *
 * @since 2.0.1
 * @package Hummingbird\Admin\Pages
 */

namespace Hummingbird\Admin\Pages;

use Hummingbird\Admin\Page;
use Hummingbird\Core\Settings as Settings_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Upgrade extends Page
 */
class Upgrade extends Page {

	/**
	 * Render the page (overwrites parent class method).
	 */
	public function render() {
		$settings = Settings_Module::get_settings( 'settings' );
		?>
		<div class="sui-wrap<?php echo $settings['accessible_colors'] ? ' sui-color-accessible ' : ' '; ?>wrap-wp-hummingbird wrap-wp-hummingbird-page <?php echo 'wrap-' . esc_attr( $this->slug ); ?>">
			<?php $this->render_inner_content(); ?>
			<?php $this->render_footer(); ?>
		</div><!-- end container -->
		<?php
	}

}
