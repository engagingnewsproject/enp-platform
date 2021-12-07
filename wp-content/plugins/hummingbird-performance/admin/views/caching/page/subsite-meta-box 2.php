<?php
/**
 * Page caching meta box.
 *
 * @package Hummingbird
 *
 * @var bool          $cdn_active        Asset optimization CDN status.
 * @var string        $deactivate_url    Deactivate URL.
 * @var bool|WP_Error $error             Error if present.
 * @var bool          $can_deactivate    Is deactivating page caching on subsites enabled.
 * @var bool          $minify_active     Asset optimization status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p><?php esc_html_e( 'Hummingbird stores static HTML copies of your pages and posts to decrease page load time.', 'wphb' ); ?></p>
<div class="sui-box-settings-row">
	<?php
	if ( is_wp_error( $error ) ) {
		$this->admin_notices->show_inline( $error->get_error_message(), 'error' );
	} else {
		$notice = esc_html__( 'Page caching is currently active.', 'wphb' );
		if ( $minify_active && $cdn_active ) {
			$notice = esc_html__( 'Page caching is currently active. You are storing your assets on the CDN in the Asset Optimization module. Hummingbird will automatically purge your cache when assets on the CDN expire (after every two months).', 'wphb' );
		}

		$this->admin_notices->show_inline( $notice );
	}
	?>
</div><!-- end row -->

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Cache Control', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'By default your subsite inherits your network admin’s cache settings.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<span class="sui-description">
				<?php
				/* translators: %s: code snippet. */
				printf(
					__( 'For any pages/post/post types/taxonomies you don’t want to cache, use the <code>%s</code> constant to instruct Hummingbird not to cache specific pages or templates.', 'wphb' ),
					esc_attr( 'define(\'DONOTCACHEPAGE\', true);', 'wphb' )
				);
				?>
		</span>
	</div>
</div>

<?php if ( $can_deactivate ) : ?>
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Deactivate', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'You can deactivate page caching at any time. Remember this may result in slower page loads unless you have another caching plugin activate.', 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<a href="<?php echo esc_url( $deactivate_url ); ?>" class="sui-button sui-button-ghost sui-button-icon-left" onclick="WPHB_Admin.Tracking.disableFeature( 'Page Caching' )">
				<span class="sui-icon-power-on-off" aria-hidden="true"></span>
				<?php esc_html_e( 'Deactivate', 'wphb' ); ?>
			</a>
			<span class="sui-description">
				<?php esc_html_e( 'Note: Deactivating won’t lose any of your website data, only the cached pages will be removed and won’t be served to your visitors any longer.', 'wphb' ); ?>
			</span>
		</div>
	</div>
<?php endif; ?>
