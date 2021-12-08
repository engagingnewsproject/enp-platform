<?php
/**
 * Page caching meta box footer on dashboard page.
 *
 * @package Hummingbird
 *
 * @since 1.7.0
 * @var string $url        Url to module.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<a href="<?php echo esc_url( $url ); ?>" class="sui-button sui-button-ghost" name="submit">
	<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
	<?php esc_html_e( 'Configure', 'wphb' ); ?>
</a>
