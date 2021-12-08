<?php
/**
 * Notifications summary meta box.
 *
 * @since 3.1.1
 * @package Hummingbird
 *
 * @var int    $active_notifications  Number of active notifications.
 * @var string $next_notification     Next scheduled notification.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branded_image = apply_filters( 'wpmudev_branding_hero_image', '' );
?>

<?php if ( $branded_image ) : ?>
	<div class="sui-summary-image-space" aria-hidden="true" style="background-image: url('<?php echo esc_url( $branded_image ); ?>')"></div>
<?php else : ?>
	<div class="sui-summary-image-space" aria-hidden="true"></div>
<?php endif; ?>
<div class="sui-summary-segment">
	<div class="sui-summary-details">
		<span class="sui-summary-large"><?php echo (int) $active_notifications; ?></span>
		<span class="sui-summary-sub">
			<?php esc_html_e( 'Active notifications', 'wphb' ); ?>
		</span>
	</div>
</div>
<div class="sui-summary-segment">
	<ul class="sui-list">
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Next scheduled reporting', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php echo esc_html( $next_notification ); ?>
			</span>
		</li>
	</ul>
</div>
