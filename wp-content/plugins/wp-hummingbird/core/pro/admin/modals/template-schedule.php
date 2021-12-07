<?php
/**
 * Notifications template: schedule.
 *
 * @since 3.1.1
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<# if ( 'uptime' === data.module && 'notifications' === data.type ) { #>
<strong><?php esc_html_e( 'Threshold', 'wphb' ); ?></strong>
<div class="sui-border-frame">
	<div class="sui-form-field">
		<label class="sui-label" for="report-threshold">
			<?php esc_html_e( 'Threshold', 'wphb' ); ?>
		</label>
		<select id="report-threshold" name="report-threshold" class="sui-select">
			<option value="0" <# if ( 0 === data.schedule.threshold ) { #>selected="selected"<# } #>><?php esc_html_e( 'Instant', 'wphb' ); ?></option>
			<option value="5" <# if ( 5 === data.schedule.threshold ) { #>selected="selected"<# } #>>5 <?php esc_html_e( 'Minutes', 'wphb' ); ?></option>
			<option value="10" <# if ( 10 === data.schedule.threshold ) { #>selected="selected"<# } #>>10 <?php esc_html_e( 'Minutes', 'wphb' ); ?></option>
			<option value="30" <# if ( 30 === data.schedule.threshold ) { #>selected="selected"<# } #>>30 <?php esc_html_e( 'Minutes', 'wphb' ); ?></option>
		</select>
	</div>
</div>
<p class="sui-description">
	<?php
	printf( /* translators: %1$s: opening a tag, %2$s: closing a tag */
		esc_html__( "We won't notify you if your website becomes available again within the specified timeframe. All downtimes are still recorded in the %1\$sdowntime report%2\$s, you just won't get notified.", 'wphb' ),
		'<a href="' . esc_url( Utils::get_admin_menu_url( 'uptime' ) . '&view=downtime' ) . '">',
		'</a>'
	);
	?>
</p>
<# } else { #>
<label class="sui-label" id="frequency-label"><?php esc_html_e( 'Frequency', 'wphb' ); ?></label>
	<div class="sui-side-tabs">
		<div class="sui-tabs-menu">
			<label for="report-frequency-daily" class="sui-tab-item <# if ( 1 === data.schedule.frequency ) { #>active<# } #>">
				<input type="radio" name="report-frequency" value="1" id="report-frequency-daily" data-tab-menu="tab-time" <# if ( 1 === data.schedule.frequency ) { #>checked="checked"<# } #>>
				<?php esc_html_e( 'Daily', 'wphb' ); ?>
			</label>

			<label for="report-frequency-weekly" class="sui-tab-item <# if ( 7 === data.schedule.frequency ) { #>active<# } #>">
				<input type="radio" name="report-frequency" value="7" id="report-frequency-weekly" data-tab-menu="tab-time" <# if ( 7 === data.schedule.frequency ) { #>checked="checked"<# } #>>
				<?php esc_html_e( 'Weekly', 'wphb' ); ?>
			</label>

			<label for="report-frequency-monthly" class="sui-tab-item <# if ( 30 === data.schedule.frequency ) { #>active<# } #>">
				<input type="radio" name="report-frequency" value="30" id="report-frequency-monthly" data-tab-menu="tab-time" <# if ( 30 === data.schedule.frequency ) { #>checked="checked"<# } #>>
				<?php esc_html_e( 'Monthly', 'wphb' ); ?>
			</label>
		</div>

		<div class="sui-tabs-content">
			<div class="sui-tab-content sui-tab-boxed active" data-tab-content="tab-time">
				<div class="sui-row sui-no-margin-bottom schedule-box">
					<div class="sui-col sui-form-field sui-no-margin-bottom <# if ( 7 !== data.schedule.frequency ) { #>sui-hidden<# } #>" data-type="week">
						<label class="sui-label" for="report-day">
							<?php esc_html_e( 'Day of the week', 'wphb' ); ?>
						</label>
						<select name="report-day" class="sui-select" id="report-day">
							<?php
							$days     = Utils::get_days_of_week();
							$send_day = $days[ array_rand( $days ) ];
							?>
							<?php foreach ( $days as $day ) : ?>
								<option value="<?php echo esc_attr( $day ); ?>" <?php selected( $day, $send_day ); ?>>
									<?php echo esc_html( ucfirst( $day ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="sui-col sui-form-field sui-no-margin-bottom <# if ( 30 !== data.schedule.frequency ) { #>sui-hidden<# } #>" data-type="month">
						<label class="sui-label" for="report-day-month">
							<?php esc_html_e( 'Day of the month', 'wphb' ); ?>
						</label>
						<select name="report-day-month" id="report-day-month" class="sui-select">
							<?php
							$days     = range( 1, 28 );
							$send_day = wp_rand( 1, 28 );
							?>
							<?php foreach ( $days as $day ) : ?>
								<option value="<?php echo esc_attr( $day ); ?>" <?php selected( $day, $send_day ); ?>>
									<?php echo esc_html( ucfirst( $day ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>

					<div class="sui-col sui-form-field sui-no-margin-bottom">
						<label class="sui-label" for="report-time">
							<?php esc_html_e( 'Time of day', 'wphb' ); ?>
						</label>
						<select name="report-time" id="report-time" class="sui-select">
							<?php $send_time = wp_rand( 0, 23 ) . ':00'; ?>
							<?php foreach ( Utils::get_times() as $time ) : ?>
								<option value="<?php echo esc_attr( $time ); ?>" <?php selected( $time, $send_time ); ?>>
									<?php echo esc_html( date_format( date_create( $time ), 'h:i A' ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
				<p id="select-single-default-helper" class="sui-description">
					<?php
					printf( /* translators: %1$s - time zone, %2$s - current time */
						esc_html__( 'Your timezone is set to %1$s, so your current time is %2$s.', 'wphb' ),
						'<strong>' . esc_html( Utils::get_timezone_string() ) . '</strong>',
						'<strong>' . esc_html( date_i18n( 'H:ia' ) ) . '</strong>'
					)
					?>
				</p>
			</div>
		</div>
	</div>
<# } #>