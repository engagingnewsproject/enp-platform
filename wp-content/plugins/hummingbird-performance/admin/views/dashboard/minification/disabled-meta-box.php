<?php
/**
 * Asset optimization disabled meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $minification_url  URL to minification module.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p><?php esc_html_e( 'Compress, combine and position your assets to dramatically improve your page load speed.', 'wphb' ); ?></p>

<a href="<?php echo esc_url( $minification_url ); ?>" class="sui-button sui-button-blue" id="minifiy-website" onclick="WPHB_Admin.Tracking.enableFeature( 'Asset Optimization' )">
	<?php esc_html_e( 'Activate', 'wphb' ); ?>
</a>
