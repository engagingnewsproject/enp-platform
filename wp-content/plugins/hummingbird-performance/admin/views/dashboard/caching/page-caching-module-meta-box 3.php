<?php
/**
 * Page caching meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $activate_url  Activate URL.
 * @var bool   $is_active     Currently active.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="sui-margin-bottom">
	<?php esc_html_e( 'Store static HTML copies of your pages and posts to reduce the processing load on your server and dramatically speed up your page load time.', 'wphb' ); ?>
</p>

<?php if ( $is_active ) : ?>
	<?php $this->admin_notices->show_inline( esc_html__( 'Page caching is currently active.', 'wphb' ) ); ?>
<?php else : ?>
	<a href="<?php echo esc_url( $activate_url ); ?>" class="sui-button sui-button-blue" id="activate-page-caching" onclick="WPHB_Admin.Tracking.enableFeature( 'Page Caching' )">
		<?php esc_html_e( 'Activate', 'wphb' ); ?>
	</a>
<?php endif; ?>
