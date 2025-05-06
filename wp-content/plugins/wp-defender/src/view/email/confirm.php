<?php
/**
 * This template is used to send email when a user subscribes to a notification.
 *
 * @package WP_Defender
 */

?>
<h1 style="font-family:inherit;font-size: 25px;line-height:30px;color:inherit;margin-top:10px;margin-bottom: 30px">
	<?php echo esc_html( $subject ); ?>
</h1>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 28px; text-align: left; word-wrap: normal;">
	<?php
	/* translators: %s: Name. */
	printf( esc_html__( 'Hi %s', 'wpdef' ), esc_html( $name ) )
	?>
	,
</p>
<p style="font-family: inherit; font-size: 16px; margin: 0 0 30px">
	<?php
	printf(
	/* translators: 1. Site url. 2. Email. 3. Notification name. */
		esc_html__(
			'An administrator from %1$s has subscribed %2$s to %3$s. To confirm your subscription, click Confirm Subscription below.',
			'wpdef'
		),
		'<strong>' . esc_url( $site_url ) . '</strong>',
		'<strong>' . esc_html( $email ) . '</strong>',
		'<strong>' . esc_html( $notification_name ) . '</strong>'
	)
	?>
</p>
<p style="margin: 0;padding: 0;text-align: center">
	<a class="button view-full"
		style="font-family: Roboto, Arial, sans-serif;font-size: 16px;font-weight: normal;line-height: 20px;text-align: center; margin-bottom:0;"
		href="<?php echo esc_url( $url ); ?>"><?php esc_attr_e( 'Confirm Subscription', 'wpdef' ); ?></a>
</p>