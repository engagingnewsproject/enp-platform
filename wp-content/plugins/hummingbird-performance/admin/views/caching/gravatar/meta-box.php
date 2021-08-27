<?php
/**
 * Gravatar caching meta box.
 *
 * @package Hummingbird
 *
 * @var string        $deactivate_url    Deactivation URL.
 * @var bool|WP_Error $error             Error if present.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p><?php esc_html_e( 'Gravatar Caching stores local copies of avatars used in comments and in your theme. You can control how often you want the cache purged depending on how your website is set up.', 'wphb' ); ?></p>
<?php
if ( is_wp_error( $error ) ) {
	$this->admin_notices->show_inline( $error->get_error_message(), 'error' );
} else {
	$this->admin_notices->show_inline( esc_html__( 'Gravatar Caching is currently active.', 'wphb' ) );
}
?>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Deactivate', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'This will deactivate Gravatar Caching and clear your local avatar storage.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<a href="<?php echo esc_url( $deactivate_url ); ?>" class="sui-button sui-button-ghost sui-button-icon-left" role="button" onclick="WPHB_Admin.Tracking.disableFeature( 'Gravatar Caching' )">
			<span class="sui-icon-power-on-off" aria-hidden="true"></span>
			<?php esc_html_e( 'Deactivate', 'wphb' ); ?>
		</a>
	</div>
</div>
