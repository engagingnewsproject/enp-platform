<?php
/**
 * Uptime notifications meta box.
 *
 * @since 1.9.3
 * @package Hummingbird
 *
 * @var \Hummingbird\Admin\Page $this
 *
 * @var string $downtime_url     URL to downtime page.
 * @var string $notice_class     Class for the notice.
 * @var string $notice_message   Message for the notice.
 * @var array  $reports_settings Settings for Uptime Reports.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pending = false;

?>

<p>
	<?php
	esc_html_e(
		'Our advanced uptime API pings this website every 2 minutes to see if everything is OK. This
	feature sends an email to nominated recipients whenever your website is very slow, or completely down.',
		'wphb'
	);
	?>
</p>

<?php $this->admin_notices->show_inline( $notice_message, $notice_class ); ?>

<form method="post" id="wphb-uptime-reporting" class="wphb-report-settings" data-module="uptime" data-name="Uptime Notifications">
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Configure', 'wphb' ); ?></span>
			<span class="sui-description">
			<?php esc_html_e( 'Choose who you want to receive uptime email notifications when your website becomes unavailable.', 'wphb' ); ?>
		</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="chk1" class="sui-toggle">
					<input type="hidden" name="scheduled-reports" value="0">
					<input type="checkbox" name="scheduled-reports" id="chk1" value="1" aria-labelledby="scheduled-reports-label" <?php checked( $reports_settings['enabled'] ); ?>>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="scheduled-reports-label" class="sui-toggle-label">
						<?php esc_html_e( 'Send an email notification when this website goes down', 'wphb' ); ?>
					</span>
				</label>
			</div>
			<div class="sui-border-frame sui-toggle-content schedule-box <?php echo $reports_settings['enabled'] ? '' : 'sui-hidden'; ?>">
				<div class="sui-recipients">
					<label class="sui-label"><?php esc_html_e( 'Recipients', 'wphb' ); ?></label>
					<?php if ( count( $reports_settings['recipients'] ) ) : ?>
						<?php
						$this->admin_notices->show_inline(
							esc_html__( "You've removed all recipients. If you save without a recipient, we'll automatically turn off notifications.", 'wphb' ),
							'warning wphb-no-recipients sui-hidden'
						);
						foreach ( $reports_settings['recipients'] as $key => $value ) :
							$input_value        = new stdClass();
							$input_value->name  = $value['name'];
							$input_value->email = $value['email'];
							$input_value        = wp_json_encode( $input_value );

							$tooltip = __( 'Subscribed', 'wphb' );
							if ( isset( $value['is_pending'] ) && $value['is_pending'] ) {
								$pending = true;
								$tooltip = __( 'Awaiting confirmation', 'wphb' );
							}
							?>
							<div class="sui-recipient">
								<span class="sui-recipient-status sui-tooltip" data-tooltip="<?php echo esc_attr( $tooltip ); ?>">
									<?php if ( isset( $value['is_pending'] ) && $value['is_pending'] ) : ?>
										<span class="sui-icon-clock" aria-hidden="true"></span>
									<?php else : ?>
										<span class="sui-icon-check-tick" aria-hidden="true"></span>
									<?php endif; ?>
								</span>
								<input data-id="<?php echo esc_attr( $key ); ?>" type="hidden" id="report-recipient" name="report-recipients[]" value="<?php echo esc_attr( $input_value ); ?>">
								<span class="sui-recipient-name"><?php echo esc_html( $value['name'] ); ?></span>
								<span class="sui-recipient-email"><?php echo esc_html( $value['email'] ); ?></span>
								<?php if ( $pending ) : ?>
									<button type="button" class="sui-button-icon wphb-resend-recipient sui-tooltip" data-tooltip="<?php esc_attr_e( 'Resend email', 'wphb' ); ?>" data-name="<?php echo esc_attr( $value['name'] ); ?>" data-email="<?php echo esc_attr( $value['email'] ); ?>">
										<span class="sui-icon-send" aria-hidden="true"></span>
									</button>
								<?php endif; ?>
								<button type="button" class="sui-button-icon wphb-remove-recipient <?php echo $pending ? '' : 'wphb-not-pending-recipient'; ?>">
									<span class="sui-icon-trash" aria-hidden="true"></span>
								</button>
							</div>
						<?php endforeach; ?>
					<?php else : ?>
						<?php
						$this->admin_notices->show_inline(
							esc_html__( "You've removed all recipients. If you save without a recipient, we'll automatically turn off notifications.", 'wphb' ),
							'warning wphb-no-recipients sui-hidden'
						);
						?>
					<?php endif; ?>
				</div>
				<a class="sui-button sui-button-ghost sui-add-recipient" data-modal-open="wphb-add-recipient-modal" data-modal-open-focus="reporting-first-name" data-modal-mask="true">
					<span class="sui-icon-plus" aria-hidden="true"></span>
					<?php esc_html_e( 'Add Recipient', 'wphb' ); ?>
				</a>

				<?php
				$this->admin_notices->show_inline(
					esc_html__( 'Recipients must confirm their subscription to begin receiving emails.', 'wphb' ),
					'grey sui-hidden wphb-confirm-sub-notice'
				);

				$classes = 'grey wphb-pending-sub-notice';
				if ( $pending && count( $reports_settings['recipients'] ) ) {
					$classes .= ' sui-hidden';
				}
				$this->admin_notices->show_inline(
					esc_html__( "Some recipients haven't confirmed their subscription to this email report. You can resend the confirmation email or remove them from the list.", 'wphb' ),
					$classes
				);
				?>
				<div class="sui-form-field">
					<label for="threshold" class="sui-label"><?php esc_html_e( 'Threshold', 'wphb' ); ?></label>
					<select id="threshold" name="threshold" class="sui-select" data-width="250">
						<option <?php selected( 0, $reports_settings['threshold'] ); ?> value="0">
							<?php esc_html_e( 'Instant', 'wphb' ); ?>
						</option>
						<option <?php selected( 5, $reports_settings['threshold'] ); ?> value="5">
							5 <?php esc_html_e( 'Minutes', 'wphb' ); ?>
						</option>
						<option <?php selected( 10, $reports_settings['threshold'] ); ?> value="10">
							10 <?php esc_html_e( 'Minutes', 'wphb' ); ?>
						</option>
						<option <?php selected( 30, $reports_settings['threshold'] ); ?> value="30">
							30 <?php esc_html_e( 'Minutes', 'wphb' ); ?>
						</option>
					</select>
				</div>
				<span class="sui-description">
				<?php
				printf(
					/* translators: %1$s: opening a tag, %2$s: closing a tag */
					esc_html__( "We won't notify you if your website becomes available again within the specified timeframe. All downtimes are still recorded in the %1\$sdowntime report%2\$s, you just won't get notified.", 'wphb' ),
					'<a href="' . esc_url( $downtime_url ) . '">',
					'</a>'
				);
				?>
			</span>
			</div>
		</div>
	</div>