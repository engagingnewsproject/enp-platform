<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title><?php
			$devman_img  = defender_asset_url('/assets/img/def-stand.svg');
			$devman_icon = defender_asset_url('/assets/img/defender-icon.png');
			$info        = defender_white_label_status();
			if (strlen($info['hero_image']) > 0) {
				$devman_img = $info['hero_image'];
			}
			bloginfo('name') ?></title>
	<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto+Condensed:400,700|Roboto:400,500,300,300italic">
	<link rel="stylesheet" href="<?php echo defender_asset_url( '/assets/css/styles.css') ?>">
	<style>
		html,
		body {
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
			background-image: url("<?php echo $devman_img; ?>");
			background-repeat: no-repeat;
			background-size: contain;
			background-position: center;
			margin-bottom: 30px;
		}

		.plugin-icon {
			width: 30px;
			height: 30px;
			margin: 0 auto;
			background-image: url("<?php echo $devman_icon; ?>");
			background-repeat: no-repeat;
			background-size: contain;
			background-position: center;
			margin-bottom: 10px;
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

		.message {
			font-size: 15px;
			line-height: 30px;
			text-align: center;
			letter-spacing: -0.25px;
			color: #666666;
		}

		#countdown-time {
			font-weight: bold;
			font-size: 28px;
			line-height: 40px;
			text-align: center;
			letter-spacing: -0.5px;
			color: #666666;
		}

		#remaining-time {
			margin-left: 10px;
		}
		.sui-icon-stopwatch::before {
			color: inherit !important;
			font-size: 24px !important;
		}

		.day-notation {
			font-weight: normal;
		}
	</style>
</head>

<body class="<?php echo 'sui-' . DEFENDER_SUI; ?>">
	<div class="wp-defender">
		<div class="container">
			<?php
			if (
				($info['hide_branding'] === false) ||
				($info['hide_branding'] === true && !empty($info['hero_image']))
			) {
				echo '<div class="image"></div>';
			}
			?>
			<p class="message"><?php echo $message ?></p>
			<?php if ( ! empty( $remaining_time ) && is_int( $remaining_time ) && $remaining_time > 0 ) { ?>
				<p class="message"><?php esc_html_e("You will be able to attempt to access again in:", 'wpdef'); ?></p>
				<p id="countdown-time"><span class="sui-icon-stopwatch" aria-hidden="true"></span><span id="remaining-time"></span></p>
			<?php } ?>
		</div>
		<?php if (!$info['hide_doc_link']) { ?>
			<div class="powered">
				<div class="plugin-icon"></div>
				<?php esc_html_e("Powered by", 'wpdef') ?>
				<strong><?php esc_html_e("Defender", 'wpdef') ?></strong>
			</div>
		<?php
		}
		?>
	</div>
	<?php if ( ! empty( $remaining_time ) && is_int( $remaining_time ) && $remaining_time > 0 ) { ?>
		<script>
			function CountDownTimer(duration, granularity) {
				this.duration = duration;
				this.granularity = granularity || 1000;
				this.tickFtns = [];
				this.running = false;
			}

			CountDownTimer.prototype.start = function() {
				if (this.running) {
					return;
				}
				this.running = true;
				var start = Date.now(),
					that = this,
					diff, obj;

				(function timer() {
					diff = that.duration - (((Date.now() - start) / 1000) | 0);

					if (diff > 0) {
						setTimeout(timer, that.granularity);
					} else {
						diff = 0;
						that.running = false;
					}

					obj = CountDownTimer.parse(diff);
					that.tickFtns.forEach(function(ftn) {
						ftn.call(this, obj);
					}, that);
				}());
			};

			CountDownTimer.prototype.onTick = function(ftn) {
				if (typeof ftn === 'function') {
					this.tickFtns.push(ftn);
				}
				return this;
			};

			CountDownTimer.prototype.expired = function() {
				return !this.running;
			};

			CountDownTimer.parse = function(seconds) {
				const DAY_IN_SECONDS = 86400;
				const HOUR_IN_SECONDS = 3600;
				const MINUTES_IN_SECONDS = 60;

				seconds = Number( seconds );

				let days = Math.floor( seconds / DAY_IN_SECONDS );

				let dayNotation = days > 1 ? 'Days' : 'Day';

				let displayDays = days > 0 ? ( days + '<span class="day-notation">&nbsp;' + dayNotation  + '&nbsp;</span>' ) : '';

				seconds %= DAY_IN_SECONDS;
				let hours = String( Math.floor( seconds / HOUR_IN_SECONDS ) ).padStart( 2, 0 );

				seconds %= HOUR_IN_SECONDS;
				let minutes = String( Math.floor( seconds / MINUTES_IN_SECONDS ) ).padStart( 2, 0 );

				seconds = String( seconds % MINUTES_IN_SECONDS ).padStart( 2, 0 );

				return displayDays + hours + ':' + minutes + ':' + seconds;
			};

			window.onload = function() {
				let display = document.getElementById("remaining-time"),
					timer = new CountDownTimer(<?php echo $remaining_time ?>);

				timer.onTick(format).onTick(pageRefresh).start();

				function pageRefresh() {
					if (this.expired()) {
						setTimeout(
							() => {
								window.location.href = window.location.href;
							},
							1000 // Intentional 1 second delay to allow browser parse headers and redirect.
						);
					}
				}

				function format(formattedTime) {
					display.innerHTML = formattedTime;
				}
			}
		</script>
	<?php } ?>
</body>
</html>