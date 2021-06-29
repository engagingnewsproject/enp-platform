<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta http-equiv="Cache-control" content="max-age=0">
	<title><?php
		$devman_img = defender_asset_url( '/assets/img/def-stand.svg' );
		$info       = ( new \WP_Defender\Behavior\WPMUDEV() )->white_label_status();
		if ( strlen( $info['hero_image'] ) > 0 ) {
			$devman_img = $info['hero_image'];
		}
		bloginfo( 'name' ) ?></title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700|Roboto:400,500,300,300italic">
	<style type="text/css">
		html, body {
			margin: 0;
			padding: 0;
			min-width: 100%;
			width: 100%;
			max-width: 100%;
			min-height: 100%;
			height: 100%;
			max-height: 100%;
		}

		.wp-defender {
			height: 100%;
			display: flex;
			align-items: center;
			font-family: Roboto;
			color: #000;
			font-size: 13px;
			line-height: 18px;
		}

		.container {
			margin: 0 auto;
			text-align: center;
		}

		.image {
			width: 128px;
			height: 128px;
			background-color: #F2F2F2;
			margin: 0 auto;
			border-radius: 50%;
			background-image: url("<?php echo $devman_img ?>");
			background-repeat: no-repeat;
			background-size: contain;
			background-position: center;
			margin-bottom: 30px;
		}

		.powered {
			position: absolute;
			bottom: 20px;
			display: block;
			text-align: center;
			width: 100%;
			font-size: 10px;
			color: #C0C0C0;
		}

		.powered strong {
			color: #8A8A8A;
			font-weight: normal;
		}
	</style>
</head>
<body>
<div class="wp-defender">
	<div class="container">
	<?php
		if ( 
			( $info['hide_branding'] === false ) || 
			( $info['hide_branding'] === true && ! empty ( $info['hero_image'] ) ) 
		) {
			echo '<div class="image"></div>';
		}
	?>
		<p><?php echo $message ?></p>
	</div>
	<div class="powered"><?php esc_html_e( "Powered by", 'wpdef' ) ?>
		<strong><?php esc_html_e( "Defender", 'wpdef' ) ?></strong>
	</div>
</div>
</body>
</html>
