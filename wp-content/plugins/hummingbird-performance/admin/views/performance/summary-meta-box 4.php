<?php
/**
 * Performance test summary meta box.
 *
 * @package Hummingbird
 *
 * @var $this Page
 *
 * @var string            $type              Report type: desktop/mobile.
 * @var stdClass|WP_Error $last_report       Last performance report.
 * @var bool              $report_dismissed  Is report dismissed.
 * @var bool              $is_doing_report   Is running a scan.
 * @var int               $opportunities     Number of failed opportunities.
 * @var int               $diagnostics       Number of failed diagnostics.
 * @var int               $passed_audits     Number of passed audits (passed opportunities + passed diagnostics).
 */

use Hummingbird\Admin\Page;
use Hummingbird\Core\Modules\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$branded_image = apply_filters( 'wpmudev_branding_hero_image', '' );
?>

<?php if ( $branded_image ) : ?>
	<div class="sui-summary-image-space" aria-hidden="true" style="background-image: url('<?php echo esc_url( $branded_image ); ?>')"></div>
<?php else : ?>
	<div class="sui-summary-image-space" aria-hidden="true"></div>
<?php endif; ?>
<div class="sui-summary-segment">
	<div class="sui-summary-details">
		<span class="sui-summary-large">
			<?php if ( ! $last_report || is_wp_error( $last_report ) || ! isset( $last_report->{$type}->score ) ) : ?>
				-
			<?php else : ?>
				<?php
				$impact_score_class = 'dismissed';
				$tooltip            = __( 'You have chosen to ignore this performance test', 'wphb' );
				if ( ! $report_dismissed ) {
					$impact_score_class = 'error';

					if ( isset( $last_report->{$type}->score ) ) {
						if ( 93 <= $last_report->{$type}->score ) {
							$tooltip = __( "Awesome job! Your site's performance is in the top 5% of all sites in the world!", 'wphb' );
						}

						if ( 81 <= $last_report->{$type}->score && 93 > $last_report->{$type}->score ) {
							$tooltip = __( 'Great job! Your site performs better than 90% of sites out there!', 'wphb' );
						}

						if ( 55 <= $last_report->{$type}->score && 80 >= $last_report->{$type}->score ) {
							$tooltip = __( 'Good job. Your site performs better than 75% of sites out there, but you can further improve things by following our recommendations below.', 'wphb' );
						}

						if ( 55 > $last_report->{$type}->score ) {
							$tooltip = __( 'Your site needs work. Check out our recommendations below to improve its performance.', 'wphb' );
						}

						if ( 90 <= $last_report->{$type}->score ) {
							$impact_score_class = 'success';
						} elseif ( 50 <= $last_report->{$type}->score ) {
							$impact_score_class = 'warning';
						}
					}
				}
				?>
				<div style="--tooltip-width: 280px;" class="sui-tooltip sui-tooltip-constrained sui-circle-score sui-circle-score-lg sui-grade-<?php echo esc_attr( $impact_score_class ); ?>" data-score="<?php echo (int) $last_report->{$type}->score; ?>" data-tooltip="<?php echo esc_attr( $tooltip ); ?>"></div>
				<div class="sui-form-field">
					<label class="sui-label sui-hidden" for="wphb-performance-report-type">
						<?php esc_html_e( 'Show results for', 'wphb' ); ?>
					</label>
					<select class="sui-select sui-select-sm" id="wphb-performance-report-type" data-width="120px">
						<option value="desktop" data-url="#desktop" <?php selected( $type, 'desktop' ); ?>>
							<?php esc_attr_e( 'Desktop', 'wphb' ); ?>
						</option>
						<option value="mobile" data-url="#mobile" <?php selected( $type, 'mobile' ); ?>>
							<?php esc_attr_e( 'Mobile', 'wphb' ); ?>
						</option>
					</select>
					<p class="sui-description">
						<?php esc_html_e( 'Performance score', 'wphb' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</span>

		<span class="sui-summary-detail">
			<?php
			if ( $last_report && ! is_wp_error( $last_report ) ) {
				$data_time    = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $last_report->time ) ) );
				$time_string  = esc_html( date_i18n( get_option( 'date_format' ), $data_time ) );
				$time_string .= sprintf(
					/* translators: %s - time in proper format */
					esc_html_x( ' at %s', 'Time of the last performance report', 'wphb' ),
					esc_html( date_i18n( get_option( 'time_format' ), $data_time ) )
				);
				echo esc_html( $time_string );
			} elseif ( $is_doing_report ) {
				$time_string = esc_html__( 'Running scan...', 'wphb' );
			} else {
				$time_string = esc_html__( 'Never', 'wphb' );
			}
			?>
		</span>
		<span class="sui-summary-sub"><?php esc_html_e( 'Last test date', 'wphb' ); ?></span>
	</div>
</div>
<div class="sui-summary-segment">
	<ul class="sui-list">
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Opportunities', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php if ( is_wp_error( $last_report ) ) : ?>
					-
				<?php else : ?>
					<a href="#wphb-opportunities">
						<?php if ( is_null( $last_report->{$type}->audits->opportunities ) ) : ?>
							<span aria-hidden="true" class="sui-icon-check-tick sui-lg sui-success"></span>
						<?php else : ?>
							<span class="sui-tag sui-tag-<?php echo esc_attr( Performance::get_audits_class( $last_report->{$type}->audits->opportunities ) ); ?>" style="cursor: pointer;">
								<?php echo esc_html( $opportunities ); ?>
							</span>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</span>
		</li>
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Diagnostics', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php if ( is_wp_error( $last_report ) ) : ?>
					-
				<?php else : ?>
					<a href="#wphb-diagnostics">
						<?php if ( is_null( $last_report->{$type}->audits->diagnostics ) ) : ?>
							<span aria-hidden="true" class="sui-icon-check-tick sui-lg sui-success"></span>
						<?php else : ?>
						<span class="sui-tag sui-tag-<?php echo esc_attr( Performance::get_audits_class( $last_report->{$type}->audits->diagnostics ) ); ?>" style="cursor: pointer;">
							<?php echo esc_html( $diagnostics ); ?>
						</span>
						<?php endif; ?>
					</a>
				<?php endif; ?>
			</span>
		</li>
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Passed audits', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php if ( is_wp_error( $last_report ) ) : ?>
					-
				<?php else : ?>
					<a href="#wphb-passed">
						<span class="sui-tag sui-tag-success" style="cursor: pointer;"><?php echo esc_html( $passed_audits ); ?></span>
					</a>
				<?php endif; ?>
			</span>
		</li>
	</ul>
</div>
