<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width">
    <title></title>
    <style>
        html {
            background-color: #F4F4F4;
        }
    </style>
</head>
<body>
<div style="margin: 306px auto 0 auto;background-color: white;text-align: center;padding:60px 60px 50px 60px;width: 600px;">
    <img src="<?php echo defender_asset_url( '/assets/img/icon-check-2x.png' ) ?>">
    <h1 style="font-family:'Open Sans', Helvetica, Arial, sans-serif;font-size: 22px;color:#333333;margin-bottom: 30px">
		<?php _e( 'Confirmed', 'wpdef' ) ?>
    </h1>
    <p style="font-family:'Open Sans', Helvetica, Arial, sans-serif;font-size: 13px;color:#666;line-height: 25px;margin-bottom: 30px">
		<?php printf( __( 'You are now subscribed to receive %s.', 'wpdef' ), $title ) ?>
    </p>
</div>
<p style="text-align: center;width: 100%;font-size: 13px;color:#888888;font-family:'Open Sans', Helvetica, Arial, sans-serif;margin-top: 20px"><?php printf( __( 'Made a mistake? <a style="font-weight: bold;text-decoration: none;color:#888888" href="%s">Unsubscribe</a>', 'wpdef' ), $url ) ?></p>
</body>
</html>