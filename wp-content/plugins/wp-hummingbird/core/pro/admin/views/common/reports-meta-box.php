<?php
/**
 * Common reports meta box for: performance reports, uptime reports, etc...
 *
 * @since 1.9.4
 *
 * @package Hummingbird
 *
 * @var \Hummingbird\Admin\Page $this
 *
 * @var array  $data            Performance report data.
 * @var bool   $enabled         Status of performance reports.
 * @var int    $frequency       Report frequency.
 * @var string $module          Report module.
 * @var string $notice_class    Class for the notice.
 * @var string $notice_message  Message for the notice.
 * @var array  $recipients      Recipients list.
 * @var string $send_day        Report send day.
 * @var string $send_time       Report send time.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( 'performance' === $module ) {
	$p_text = __( 'Enable scheduled performance tests and get the customized results emailed directly to your inbox.', 'wphb' );
} elseif ( 'uptime' === $module ) {
	$p_text = __( 'Enable scheduled email reports direct to recipient inboxes of your choice. The report will include response time data and any downtime logs in the selected period.', 'wphb' );
}
?>

<p><?php echo esc_html( $p_text ); ?></p>

<?php $this->admin_notices->show_inline( $notice_message, $notice_class ); ?>

<form method="post" class="wphb-report-settings" id="wphb-<?php echo esc_attr( $module ); ?>-reporting" data-module="<?php echo esc_attr( $module ); ?>" data-name="<?php echo esc_attr( ucfirst( $module ) ); ?> Reports">
	<div class="sui-box-settings-row <?php echo $enabled ? '' : 'wphb-first-of-type'; ?>">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Email Reports', 'wphb' ); ?>
			</span>
			<span class="sui-description">
				<?php esc_html_e( 'Choose from daily, weekly or monthly email reports.', 'wphb' ); ?>
			</span>
			<?php if ( 'uptime' === $module && is_network_admin() ) : ?>
				<span class="sui-description">
					<?php esc_html_e( 'Note: Scheduled uptime reports are only available for network sites and not subsites. This is because the network sites and subsites are located on the same server, meaning that subsite uptime reports would be redundant.', 'wphb' ); ?>
				</span>
			<?php endif; ?>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="chk1" class="sui-toggle">
					<input type="hidden" name="scheduled-reports" value="0"/>
					<input type="checkbox" name="scheduled-reports" id="chk1" value="1" aria-labelledby="scheduled-reports-label" <?php checked( 1, $enabled ); ?>>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="scheduled-reports-label" class="sui-toggle-label">
						<?php
						printf( /* translators: %s: module name */
							esc_html__( 'Send scheduled %s reports', 'wphb' ),
							esc_html( $module )
						);
						?>
					</span>
				</label>
			</div>
			<div class="sui-border-frame sui-toggle-content schedule-box <?php echo $enabled ? '' : 'sui-hidden'; ?>">
				<div class="sui-recipients">
					<label class="sui-label"><?php esc_html_e( 'Recipients', 'wphb' ); ?></label>
					<?php if ( count( $recipients ) ) : ?>
						<?php
						$this->admin_notices->show_inline(
							esc_html__( "You've removed all recipients. If you save without a recipient, we'll automatically turn off reports.", 'wphb' ),
							'warning wphb-no-recipients sui-hidden'
						);
						?>
						<?php foreach ( $recipients as $key => $id ) : ?>
							<?php
							$input_value        = new stdClass();
							$input_value->name  = $id['name'];
							$input_value->email = $id['email'];
							$input_value        = wp_json_encode( $input_value );
							?>
							<div class="sui-recipient">
								<input data-id="<?php echo esc_attr( $key ); ?>" type="hidden" id="report-recipient" name="report-recipients[]" value="<?php echo esc_attr( $input_value ); ?>">
								<span class="sui-recipient-name"><?php echo esc_html( $id['name'] ); ?></span>
								<span class="sui-recipient-email"><?php echo esc_html( $id['email'] ); ?></span>
								<button data-id="<?php echo esc_attr( $key ); ?>" type="button" class="sui-button-icon wphb-remove-recipient"><span class="sui-icon-trash" aria-hidden="true"></span></button>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<?php
						$this->admin_notices->show_inline(
							esc_html__( "You've removed all recipients. If you save without a recipient, we'll automatically turn off reports.", 'wphb' ),
							'warning wphb-no-recipients'
						);
						?>
					<?php endif; ?>
				</div>
				<a class="sui-button sui-button-ghost sui-add-recipient" data-modal-open="wphb-add-recipient-modal" data-modal-open-focus="reporting-first-name" data-modal-mask="true">
					<span class="sui-icon-plus" aria-hidden="true"></span>
					<?php esc_html_e( 'Add Recipient', 'wphb' ); ?>
				</a>
				<div class="sui-form-field">
					<label for="report-frequency" class="sui-label"><?php esc_html_e( 'Schedule', 'wphb' ); ?></label>
					<select name="report-frequency" class="sui-select" data-width="250" id="report-frequency">
						<option <?php selected( 1, $frequency ); ?> value="1">
							<?php esc_html_e( 'Daily', 'wphb' ); ?>
						</option>
						<option <?php selected( 7, $frequency ); ?> value="7">
							<?php esc_html_e( 'Weekly', 'wphb' ); ?>
						</option>
						<option <?php selected( 30, $frequency ); ?> value="30">
							<?php esc_html_e( 'Monthly', 'wphb' ); ?>
						</option>
					</select>
				</div>

				<div class="sui-row">
					<div class="sui-col sui-form-field sui-no-margin-bottom days-container" data-type="week">
						<label class="sui-label" for="report-day">
							<?php esc_html_e( 'Day of the week', 'wphb' ); ?>
						</label>
						<select name="report-day" class="sui-select" data-width="250" id="report-day">
							<?php foreach ( \Hummingbird\Core\Utils::get_days_of_week() as $day ) : ?>
								<option <?php selected( $day, $send_day ); ?> value="<?php echo esc_attr( $day ); ?>">
									<?php echo esc_html( ucfirst( $day ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="sui-col sui-form-field sui-no-margin-bottom days-container sui-hidden" data-type="month">
						<label class="sui-label" for="report-day-month">
							<?php esc_html_e( 'Day of the month', 'wphb' ); ?>
						</label>
						<select name="report-day-month" id="report-day-month" class="sui-select" data-width="250">
							<?php
							$days = range( 1, 28 );
							if ( ! in_array( $send_day, $days ) ) {
								$send_day = rand( 1, 28 );
							}
							?>
							<?php foreach ( $days as $day ) : ?>
								<option <?php selected( $day, $send_day ); ?> value="<?php echo esc_attr( $day ); ?>">
									<?php echo esc_html( ucfirst( $day ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
					<div class="sui-col sui-form-field">
						<label class="sui-label" for="report-time">
							<?php esc_html_e( 'Time of day', 'wphb' ); ?>
						</label>
						<select name="report-time" id="report-time" class="sui-select" data-width="250">
							<?php foreach ( \Hummingbird\Core\Utils::get_times() as $time ) : ?>
								<option <?php selected( $time, $send_time ); ?> value="<?php echo esc_attr( $time ); ?>">
									<?php echo esc_html( strftime( '%I:%M %p', strtotime( $time ) ) ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</div>
				</div>
			</div><!-- end sui-border-frame -->
		</div>
	</div>

<?php if ( 'performance' === $module ) : ?>
	<div class="sui-box-settings-row <?php echo $enabled ? '' : 'sui-hidden'; ?>" id="performance-customizations">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label">
				<?php esc_html_e( 'Customize', 'wphb' ); ?>
			</span>
				<span class="sui-description">
				<?php esc_html_e( 'Choose your email preferences for the performance test results.', 'wphb' ); ?>
			</span>
		</div>

		<div class="sui-box-settings-col-2">
			<strong><?php esc_html_e( 'Device', 'wphb' ); ?></strong>
			<span class="sui-description">
				<?php esc_html_e( 'Choose which device you want to show the performance test results for in your scheduled performance test reports.', 'wphb' ); ?>
			</span>

			<div class="sui-side-tabs">
				<div class="sui-tabs-menu">
					<label for="report_type-desktop" class="sui-tab-item <?php echo 'desktop' === $data['type'] ? 'active' : ''; ?>">
						<input type="radio" name="report-type" value="desktop" id="report_type-desktop" <?php checked( $data['type'], 'desktop' ); ?>>
						<?php esc_html_e( 'Desktop', 'wphb' ); ?>
					</label>

					<label for="report_type-mobile" class="sui-tab-item <?php echo 'mobile' === $data['type'] ? 'active' : ''; ?>">
						<input type="radio" name="report-type" value="mobile" id="report_type-mobile" <?php checked( $data['type'], 'mobile' ); ?>>
						<?php esc_html_e( 'Mobile', 'wphb' ); ?>
					</label>

					<label for="report_type-both" class="sui-tab-item <?php echo 'both' === $data['type'] ? 'active' : ''; ?>">
						<input type="radio" name="report-type" value="both" id="report_type-both" <?php checked( $data['type'], 'both' ); ?>>
						<?php esc_html_e( 'Both', 'wphb' ); ?>
					</label>
				</div>
			</div>

			<strong><?php esc_html_e( 'Results', 'wphb' ); ?></strong>
			<span class="sui-description">
				<?php esc_html_e( 'Choose what results do you want to see in your scheduled performance test reports.', 'wphb' ); ?>
			</span>

			<label for="metrics" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
				<input type="checkbox" name="metrics" id="metrics" <?php checked( $data['metrics'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( 'Score Metrics', 'wphb' ); ?></span>
			</label>
			<label for="audits" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
				<input type="checkbox" name="audits" id="audits" <?php checked( $data['audits'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( 'Audits', 'wphb' ); ?></span>
			</label>
			<label for="field-data" class="sui-checkbox sui-checkbox-stacked sui-checkbox-sm">
				<input type="checkbox" name="field-data" id="field-data" <?php checked( $data['historic'] ); ?> />
				<span aria-hidden="true"></span>
				<span><?php esc_html_e( 'Historic Field Data', 'wphb' ); ?></span>
			</label>
		</div>
	</div>
<?php endif; ?>