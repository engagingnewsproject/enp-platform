<?php
/**
 * Gzip meta box footer on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $gzip_url  Url to gzip module.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<a href="<?php echo esc_url( $gzip_url ); ?>" class="sui-button sui-button-ghost">
	<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
	<?php esc_html_e( 'Configure', 'wphb' ); ?>
</a>
