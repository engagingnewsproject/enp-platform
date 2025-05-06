<?php
/**
 * This template is used to send email when an IP address is locked out.
 *
 * @package WP_Defender
 */

?>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 28px; text-align: left; word-wrap: normal;">
	<?php
	/* translators: %s: Name. */
	printf( esc_html__( 'Hi %s', 'wpdef' ), esc_html( $name ) )
	?>
	,
</p>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 24px; text-align: left;">
	<?php
	esc_html_e(
		'We have received a request to unblock an IP address that appears to be locked out of your site.',
		'wpdef'
	);
	?>
</p>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 24px; text-align: left;">
	<?php
	/* translators: %s: Generated time. */
	printf( esc_html__( 'This request was generated on %s.', 'wpdef' ), esc_html( $generated_time ) );
	?>
</p>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 24px; text-align: left;">
	<?php
	printf(
		/* translators: %s: Unlocked link. */
		esc_html__( 'If this request was made by you and you would like to unblock your IP address, please click on the following link: %s.', 'wpdef' ),
		'<a href="' . esc_url( $unlocked_link ) . '">' . esc_url( $unlocked_link ) . '</a>'
	);
	?>
</p>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 24px; text-align: left;">
	<?php
	esc_html_e(
		'Please note that this link is only valid for 30 minutes from the time of generation.',
		'wpdef'
	);
	?>
</p>
<p style="font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 24px; text-align: center;">
	<?php
	printf(
		'<a class="button view-full" href="%1$s">%2$s</a>',
		esc_url( $unlocked_link ),
		esc_html__( 'Unlock me', 'wpdef' )
	);
	?>
</p>