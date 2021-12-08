<?php
/**
 * Page caching header meta box.
 *
 * @package Hummingbird
 *
 * @var string $title      Module title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<div class="sui-actions-right">
	<span class="spinner"></span>
	<a href="#" class="sui-button sui-button-ghost sui-tooltip sui-tooltip-top-right" data-tooltip="<?php esc_attr_e( 'Clear all locally cached static pages', 'wphb' ); ?>">
		<?php esc_html_e( 'Clear cache', 'wphb' ); ?>
	</a>
</div>
