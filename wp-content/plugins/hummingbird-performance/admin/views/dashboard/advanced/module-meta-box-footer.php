<?php
/**
 * Advanced tools meta box footer.
 *
 * @since 1.8
 * @package Hummingbird
 *
 * @var string $url  Url to settings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<a href="<?php echo esc_url( $url ); ?>" class="sui-button sui-button-ghost">
	<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
	<?php esc_html_e( 'Configure', 'wphb' ); ?>
</a>
