<?php
/**
 * Rss caching met box.
 *
 * @since 1.8
 * @package Hummingbird
 *
 * @var int    $duration  Rss cache duration.
 * @var string $url       Activate/deactivate link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'By default, WordPress will cache your RSS feeds to reduce the load on your server – which is a great feature. Hummingbird gives you control over the expiry time, or you can disable it all together.', 'wphb' ); ?>
</p>

<?php $this->admin_notices->show_inline( esc_html__( 'RSS Feed Caching is currently active.', 'wphb' ) ); ?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Expiry time', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Choose the length of time you want WordPress to cache your RSS feed for. The longer you cache it for, the less load on your server.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<div class="sui-form-field">
			<input id="rss-expiry-time" name="rss-expiry-time" class="sui-form-control sui-input-sm sui-field-has-suffix" aria-label="<?php esc_attr_e( 'Expiry time', 'wphb' ); ?>" value="<?php echo absint( $duration ); ?>">
			<span class="sui-field-suffix"><?php esc_html_e( 'seconds', 'wphb' ); ?></span>
			<span id="description-unique-id" class="sui-description">
				<?php esc_html_e( 'Note: The default expiry is set to one hour.', 'wphb' ); ?>
			</span>
		</div>
	</div>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Disable caching', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( "If you don't want your RSS Feed cached, you can disable it here.", 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<a href="<?php echo esc_url( $url ); ?>" class="sui-button sui-button-ghost" role="button" onclick="WPHB_Admin.Tracking.disableFeature( 'RSS Caching' )">
			<?php esc_html_e( 'Disable Caching', 'wphb' ); ?>
		</a>
	</div>
</div>
