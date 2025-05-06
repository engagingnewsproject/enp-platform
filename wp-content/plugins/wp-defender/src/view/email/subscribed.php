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
	printf( esc_html__( 'Hi %s', 'wpdef' ), esc_html( $name ) );
	?>
	,
</p>
<p style="font-family: inherit; font-size: 16px; margin: 0 0 30px">
	<?php
	$unsubscribe  = '<a style="text-decoration: none;color:#0059FF;" href="' . esc_url( $url ) . '">';
	$unsubscribe .= esc_html__( 'unsubscribe', 'wpdef' );
	$unsubscribe .= '</a>';
	printf(
	/* translators: 1. Notification name. 2. Unsubscribe-action. */
		esc_html__(
			'You are now subscribed to %1$s. If you no longer wish to receive these emails, you can %2$s.',
			'wpdef'
		),
		esc_html( $notification_name ),
		wp_kses(
			$unsubscribe,
			array(
				'a' => array(
					'href'  => array(),
					'style' => array(),
				),
			)
		)
	);
	?>
</p>