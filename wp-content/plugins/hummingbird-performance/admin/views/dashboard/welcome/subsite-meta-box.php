<?php
/**
 * Dashboard summary meta box.
 *
 * @since 2.0.0
 * @package Hummingbird
 *
 * @var bool   $caching_enabled   Page caching status.
 * @var bool   $database_items    Available items to purge in Advanced Tools.
 * @var bool   $is_doing_report   If is doing performance report.
 * @var object $last_report       Last report object.
 * @var bool   $minify_enabled    Asset optimization status.
 * @var bool   $report_dismissed  Last report dismissed warning.
 * @var string $report_type        Performance report type: desktop or mobile.
 */

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
			<?php if ( ! $last_report || is_wp_error( $last_report ) || ! isset( $last_report->{$report_type}->score ) ) : ?>
				-
			<?php else : ?>
				<?php
				$impact_score_class = 'dismissed';
				$tooltip            = __( 'You have chosen to ignore this performance test', 'wphb' );
				if ( ! $report_dismissed ) {
					$impact_score_class = 'error';

					if ( isset( $last_report->{$report_type}->score ) ) {
						if ( 93 <= $last_report->{$report_type}->score ) {
							$tooltip = __( "Awesome job! Your site's performance is in the top 5% of all sites in the world!", 'wphb' );
						}

						if ( 81 <= $last_report->{$report_type}->score && 93 > $last_report->{$report_type}->score ) {
							$tooltip = __( 'Great job! Your site performs better than 90% of sites out there!', 'wphb' );
						}

						if ( 55 <= $last_report->{$report_type}->score && 80 >= $last_report->{$report_type}->score ) {
							$tooltip = __( 'Good job. Your site performs better than 75% of sites out there, but you can further improve things by following our recommendations below.', 'wphb' );
						}

						if ( 55 > $last_report->{$report_type}->score ) {
							$tooltip = __( 'Your site needs work. Check out our recommendations below to improve its performance.', 'wphb' );
						}

						if ( 90 <= $last_report->{$report_type}->score ) {
							$impact_score_class = 'success';
						} elseif ( 50 <= $last_report->{$report_type}->score ) {
							$impact_score_class = 'warning';
						}
					}
				}
				?>
				<div style="--tooltip-width: 280px;" class="sui-tooltip sui-tooltip-constrained sui-circle-score sui-circle-score-lg sui-grade-<?php echo esc_attr( $impact_score_class ); ?>" data-score="<?php echo (int) $last_report->{$report_type}->score; ?>" data-tooltip="<?php echo esc_attr( $tooltip ); ?>"></div>
				<div class="sui-form-field">
					<label class="sui-label sui-hidden" for="wphb-performance-report-type">
						<?php esc_html_e( 'Show results for', 'wphb' ); ?>
					</label>
					<select class="sui-select sui-select-sm" id="wphb-performance-report-type" data-width="120px">
						<option value="desktop" data-url="#desktop" <?php selected( $report_type, 'desktop' ); ?>>
							<?php esc_attr_e( 'Desktop', 'wphb' ); ?>
						</option>
						<option value="mobile" data-url="#mobile" <?php selected( $report_type, 'mobile' ); ?>>
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
			<span class="sui-list-label"><?php esc_html_e( 'Page Caching', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<span class="<?php echo $caching_enabled ? 'sui-tag sui-tag-success' : 'sui-tag'; ?>">
					<?php $caching_enabled ? esc_html_e( 'Active', 'wphb' ) : esc_html_e( 'Inactive', 'wphb' ); ?>
				</span>
			</span>
		</li>
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Asset Optimization', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<span class="<?php echo $minify_enabled ? 'sui-tag sui-tag-success' : 'sui-tag'; ?>">
					<?php $minify_enabled ? esc_html_e( 'Active', 'wphb' ) : esc_html_e( 'Inactive', 'wphb' ); ?>
				</span>
			</span>
		</li>
		<li>
			<span class="sui-list-label"><?php esc_html_e( 'Database Cleanup', 'wphb' ); ?></span>
			<span class="sui-list-detail">
				<?php
				printf(
					/* translators: %d - number of entries */
					esc_html__( '%d dispensable entries', 'wphb' ),
					absint( $database_items )
				)
				?>
			</span>
		</li>
	</ul>
</div>
