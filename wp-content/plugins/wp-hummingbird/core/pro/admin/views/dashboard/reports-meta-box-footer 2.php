<?php
/**
 * Dashboard notifications meta box footer.
 *
 * @since 3.1.1
 * @package Hummingbird
 *
 * @var string $notifications_url  Notifications URL.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<a href="<?php echo esc_url( $notifications_url ); ?>" role="button" class="sui-button sui-button-ghost">
	<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
	<?php esc_html_e( 'Manage Notifications', 'wphb' ); ?>
</a>