<?php
/**
 * Browser caching meta box footer on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $caching_url  Url to browser caching module.
 * @var bool $cf_active      Is Cloudflare connected.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<a href="<?php echo esc_url( $caching_url ); ?>" class="sui-button sui-button-ghost" name="submit">
	<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
	<?php esc_html_e( 'Configure', 'wphb' ); ?>
</a>
<?php if ( $cf_active ) : ?>
	<div class="sui-actions-right">
		<span class="status-text">
			<?php esc_html_e( 'Cloudflare is connected', 'wphb' ); ?>
		</span>
	</div>
<?php endif; ?>
