<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
    <title><?php echo $subject ?></title>
    <style>
        html {
            background-color: #F4F4F4;
        }
    </style>
</head>
<body>
<div style="margin: 60px auto 0 auto;background-color: white;padding:60px 60px 50px 60px;width: 600px;">
    <h1 style="font-family:'Open Sans', Helvetica, Arial, sans-serif;font-size: 22px;color:#333333;margin-bottom: 30px">
		<?php echo $subject ?>
    </h1>
    <p style="font-family:'Open Sans', Helvetica, Arial, sans-serif;font-size: 18px;color:#666;line-height: 25px;margin-bottom: 30px">
		<?php printf( __( 'An administrator from <strong>%s</strong> has subscribed <strong>%s</strong> to receive <strong>%s</strong>. Confirm your subscription by clicking the button below.', 'wpdef' ),
			network_site_url(), $email, $notification_name ) ?>
    </p>
    <p style="margin-bottom: 30px">
        <a style="display: inline-block;padding:13px 20px 11px 20px;background-color: #17A8E3;color:white;text-transform: uppercase;
text-decoration: none;font-family: Helvetica, Arial, sans-serif;font-size: 13px;font-weight: bold"
           href="<?php echo $url ?>"><?php _e( 'Confirm Subscription', 'wpdef' ) ?></a>
    </p>
    <p style="color:#888888;font-family:'Open Sans', Helvetica, Arial, sans-serif;font-size: 13px;line-height: 22px">
		<?php _e( 'If you received this email by mistake, simply delete it. You won’t be subscribed if you don’t hit the confirm button above.' ) ?>
    </p>
</div>
<p style="text-align: center;width: 100%;font-size: 13px;color:#888888;font-family:'Open Sans', Helvetica, Arial, sans-serif;margin-top: 20px"><?php printf( __( 'This email was sent from <strong>%s</strong>', 'wpdef' ), network_site_url() ) ?></p>
</body>
</html>