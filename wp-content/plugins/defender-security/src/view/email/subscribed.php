<h1 style="font-family:inherit;font-size: 25px;line-height:30px;color:inherit;margin-top:10px;margin-bottom: 30px">
	<?php echo $subject ?>
</h1>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 28px; text-align: left; word-wrap: normal;">
	<?php printf( __( "Hi %s", 'wpdef' ), $name ) ?>,
</p>
<p style="font-family: inherit; font-size: 16px; margin: 0 0 30px">
	<?php
	printf(
		/* translators: %s - notification_name, %s - url */
		__('You are now subscribed to %s. If you no longer wish to receive these emails, you can <a style="text-decoration: none;color:#286EFA" href="%s">unsubscribe</a>.', 'wpdef' ),
		$notification_name,
		$url
	);
	?>
</p>
