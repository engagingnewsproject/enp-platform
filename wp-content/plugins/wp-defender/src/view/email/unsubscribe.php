<?php
/**
 * This template is used to unsubscribe from notifications.
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
	/* translators: 1. Notification name. 2. Resubscribe-action. */
		esc_html__(
			'You are now unsubscribed from %1$s. If you made a mistake and wish to continue receiving these emails, you can %2$s.',
			'wpdef'
		),
		esc_html( $notification_name ),
		'<a style="text-decoration: none;" href="' . esc_url( $url ) . '">' . esc_html__( 'resubscribe', 'wpdef' ) . '</a>'
	);
	?>
</p>