<?php
/**
 * Browser caching settings header meta box.
 *
 * @package Hummingbird
 *
 * @var string $title      Title of the module.
 * @var bool   $cf_active  Cloudflare status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<?php if ( ! $cf_active ) : ?>
	<div class="sui-actions-right">
		<p class="wphb-label-notice-inline sui-hidden-xs sui-hidden-sm">
			<?php esc_html_e( 'Using Cloudflare?', 'wphb' ); ?>
			<a href="#" data-modal-open="cloudflare-connect" data-modal-open-focus="cloudflare-email" data-modal-close-focus="wrap-wphb-browser-caching" data-modal-mask="false" data-esc-close="false">
				<?php esc_html_e( 'Connect account', 'wphb' ); ?>
			</a>
		</p>
	</div>
<?php endif; ?>
