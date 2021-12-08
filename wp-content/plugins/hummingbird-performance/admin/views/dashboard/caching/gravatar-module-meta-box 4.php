<?php
/**
 * Gravatar caching meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $caching_url     Caching URL.
 * @var string $activate_url    Activate URL.
 * @var bool   $is_active       Currently active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="sui-margin-bottom"><?php esc_html_e( 'Store local copies of Gravatars to avoid your visitors loading them on every page load.', 'wphb' ); ?></p>
<?php
if ( $is_active ) {
	$this->admin_notices->show_inline( esc_html__( 'Gravatar caching is currently active.', 'wphb' ) );
}
?>

<?php if ( ! $is_active ) : ?>
	<a href="<?php echo esc_url( $activate_url ); ?>" class="sui-button sui-button-blue" id="activate-page-caching" onclick="WPHB_Admin.Tracking.enableFeature( 'Gravatar Caching' )">
		<?php esc_html_e( 'Activate', 'wphb' ); ?>
	</a>
<?php endif; ?>
