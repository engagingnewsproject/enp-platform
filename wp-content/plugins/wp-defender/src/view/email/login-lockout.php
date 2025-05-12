<?php
/**
 * This template is used to send login lockout email.
 *
 * @package WP_Defender
 */

?>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 28px; text-align: left; word-wrap: normal;">
	<?php
	/* translators: %s: Name. */
	printf( esc_html__( 'Hi %s', 'wpdef' ), esc_html( $name ) );
	?>
	,
</p>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 24px; text-align: left;">
	<?php
		// $text has html tags from src\model\notification\class-firewall-notification.php.
		echo wp_kses_post( $text );
	?>
</p>
<p style="font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 24px; text-align: center;">
	<?php
	printf(
		'<a class="button view-full" href="%s">' . esc_html__( 'View Full Logs', 'wpdef' ) . '</a>',
		esc_url( $logs_url )
	);
	?>
</p>