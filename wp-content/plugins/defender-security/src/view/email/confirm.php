<h1 style="font-family:inherit;font-size: 25px;line-height:30px;color:inherit;margin-top:10px;margin-bottom: 30px">
    <?php echo $subject ?>
</h1>
<p style="color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 16px; font-weight: normal; line-height: 24px; margin: 0; padding: 0 0 28px; text-align: left; word-wrap: normal;">
	<?php printf( __( "Hi %s", 'wpdef' ), $name ) ?>,
</p>
<p style="font-family: inherit; font-size: 16px; margin: 0 0 30px">
    <?php printf( __( 'An administrator from <strong>%s</strong> has subscribed <strong>%s</strong> to <strong>%s</strong>. To confirm your subscription, click Confirm Subscription below.', 'wpdef' ),
        $site_url, $email, $notification_name ) ?>
</p>
<p style="margin: 0;padding: 0;text-align: center">
    <a class="button"
       style="font-family: Roboto, Arial, sans-serif;font-size: 16px;font-weight: normal;line-height: 20px;text-align: center; margin-bottom:0;"
       href="<?php echo $url ?>"><?php _e( 'Confirm Subscription', 'wpdef' ) ?></a>
</p>
