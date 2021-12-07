<?php
/**
 * Main template file.
 *
 * @package Hummingbird
 *
 * @var array  $params     Parameters array: REPORT_TYPE, USER_NAME, NOTIFICATIONS_URL, FULL_REPORT_URL, SITE_MANAGE_URL, SITE_URL, SITE_NAME.
 * @var object $last_test  Latest performance report.
 */

use Hummingbird\Core\Modules\Performance;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<table class="wrapper main" align="center" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
	<tbody>
	<tr style="padding: 0; text-align: left; vertical-align: top;">
		<td class="wrapper-inner main-inner" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #555555; font-family: 'Open Sans', Arial, sans-serif; font-size: 14px; font-weight: normal; hyphens: auto; line-height: 30px; margin: 0; padding: 40px 60px; text-align: left; vertical-align: top; word-wrap: break-word;">

			<table class="main-content" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
				<tbody>
				<tr style="padding: 0; text-align: left; vertical-align: top;">
					<td class="main-content-text" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #333333; font-family: 'Open Sans', Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 30px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
						<?php /* translators: %s: Username. */ ?>
						<p style="color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 18px;font-weight: normal;line-height: 24px;margin: 0 0 10px;padding: 0;text-align: left"><?php printf( esc_html__( 'Hi %s,', 'wphb' ), esc_attr( $params['USER_NAME'] ) ); ?></p>

						<?php
						$data_time    = strtotime( get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $last_test->time ) ) );
						$time_string  = esc_html( date_i18n( get_option( 'date_format' ), $data_time ) );
						$time_string .= sprintf(
							/* translators: %s - time in proper format */
							esc_html_x( ' at %s', 'Time of the last performance report', 'wphb' ),
							esc_html( date_i18n( get_option( 'time_format' ), $data_time ) )
						);
						?>
						<p style="color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 18px;font-weight: normal;line-height: 28px;margin: 0 0 30px;padding: 0;text-align: left;letter-spacing: -0.3px;">
							<?php esc_html_e( 'Here’s your latest Performance Test summary of', 'wphb' ); ?>&nbsp;
							<a class="brand" href="<?php echo esc_attr( $params['SITE_MANAGE_URL'] ); ?>" target="_blank" style="color: #17A8E3;font-family: 'Open Sans', Arial, sans-serif;font-weight: inherit;line-height: 30px;margin: 0;padding: 0;text-align: left;text-decoration: none">
								<?php echo esc_html( $params['SITE_URL'] ); ?>
							</a>&nbsp;
							<?php
							printf( /* translators: %s - tested on */
								__( 'tested on %s.', 'wphb' ),
								$time_string
							);
							?>
						</p>

						<p style="color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 25px;font-weight: 600;line-height: 34px;margin: 0 0 5px;padding: 0;text-align: left;">
							<?php esc_html_e( 'Overall Score', 'wphb' ); ?>
						</p>

						<p style="color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 14px;letter-spacing: -0.23px;line-height: 22px;margin: 0 0 15px;padding: 0;text-align: left;">
							<?php
							if ( 'both' === $params['DEVICE'] ) {
								esc_html_e( 'Here are your latest performance test results. A score above 91 on desktop and 74 on mobile is considered as a good benchmark.', 'wphb' );
							} elseif ( 'desktop' === $params['DEVICE'] ) {
								esc_html_e( 'Here are your latest performance test results. A score above 91 on desktop is considered as a good benchmark.', 'wphb' );
							} elseif ( 'mobile' === $params['DEVICE'] ) {
								esc_html_e( 'Here are your latest performance test results. A score above 74 on mobile is considered as a good benchmark.', 'wphb' );
							}
							?>
						</p>

						<table class="reports-list" align="center" style="border-collapse: collapse;border-spacing: 0;margin: 0 0 30px;padding: 0;text-align: left;vertical-align: top;width: 100%">
							<thead>
							<tr style="background-color: #F2F2F2">
								<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
									<td style="border-radius: 4px 0 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: center">
										<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
										<span style="margin-left: 5px"><?php esc_html_e( 'Desktop', 'wphb' ); ?></span>
									</td>
								<?php endif; ?>
								<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
									<td style="border-radius: 0 4px 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: center">
										<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
										<span style="margin-left: 5px"><?php esc_html_e( 'Mobile', 'wphb' ); ?></span>
									</td>
								<?php endif; ?>
							</tr>
							</thead>
							<tbody>
							<tr class="report-list-item">
								<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
									<td class="report-list-item-result" align="center" style="border: 1px solid #F2F2F2;color: #555555;font-family: 'Open Sans', Arial, sans-serif;font-size: 50px;font-weight: 600">
										<table>
											<tr>
												<td rowspan="2"><?php echo absint( $last_test->desktop->score ); ?></td>
												<td style="text-align: left">
													<?php if ( 'a' === $last_test->desktop->score_class ) : ?>
														<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-success.png' ); ?>" alt="<?php esc_attr_e( 'Ok', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; height: 16px; outline: none; text-decoration: none; width: auto;" />
													<?php elseif ( 'b' === $last_test->desktop->score_class ) : ?>
														<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-warning.png' ); ?>" alt="<?php esc_attr_e( 'Warning', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; height: 16px; outline: none; text-decoration: none; width: auto;" />
													<?php elseif ( 'c' === $last_test->desktop->score_class ) : ?>
														<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-error.png' ); ?>" alt="<?php esc_attr_e( 'Critical', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; height: 16px; outline: none; text-decoration: none; width: auto;" />
													<?php endif; ?>
												</td>
											</tr>
											<tr>
												<td>
													<span style="color: #555555;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: normal;line-height: 22px;letter-spacing: -0.3px;width: 300px;vertical-align: top">/100</span>
												</td>
											</tr>
										</table>
									</td>
								<?php endif; ?>
								<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
									<td class="report-list-item-result" align="center" style="border: 1px solid #F2F2F2;color: #555555;font-family: 'Open Sans', Arial, sans-serif;font-size: 50px;font-weight: 600">
										<table>
											<tr>
												<td rowspan="2"><?php echo absint( $last_test->mobile->score ); ?></td>
												<td style="text-align: left">
													<?php if ( 'a' === $last_test->mobile->score_class ) : ?>
														<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-success.png' ); ?>" alt="<?php esc_attr_e( 'Ok', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; height: 16px; outline: none; text-decoration: none; width: auto;" />
													<?php elseif ( 'b' === $last_test->desktop->score_class ) : ?>
														<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-warning.png' ); ?>" alt="<?php esc_attr_e( 'Warning', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; height: 16px; outline: none; text-decoration: none; width: auto;" />
													<?php elseif ( 'c' === $last_test->desktop->score_class ) : ?>
														<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-error.png' ); ?>" alt="<?php esc_attr_e( 'Critical', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; height: 16px; outline: none; text-decoration: none; width: auto;" />
													<?php endif; ?>
												</td>
											</tr>
											<tr>
												<td>
													<span style="color: #555555;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: normal;line-height: 22px;letter-spacing: -0.3px;width: 300px;vertical-align: top">/100</span>
												</td>
											</tr>
										</table>
									</td>
								<?php endif; ?>
							</tr>
							</tbody>
						</table>

						<?php if ( $params['SHOW_METRICS'] ) : ?>
							<p style="color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 25px;font-weight: 600;line-height: 34px;margin: 0 0 5px;padding: 0;text-align: left;">
								<?php esc_html_e( 'Score Metrics', 'wphb' ); ?>
							</p>

							<p style="color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 14px;letter-spacing: -0.23px;line-height: 22px;margin: 0 0 15px;padding: 0;text-align: left;">
								<?php esc_html_e( 'Your performance score is calculated based on how your site performs on each of the following 6 metrics.', 'wphb' ); ?>
							</p>

							<table class="reports-list" align="center" style="border-collapse: collapse;border-spacing: 0;margin: 0 0 30px;padding: 0;text-align: left;vertical-align: top;width: 100%">
								<thead>
								<tr style="background-color: #F2F2F2">
									<td style="padding-left: 20px;border-radius: 4px 0 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: left">
										<?php esc_html_e( 'Metrics', 'wphb' ); ?>
									</td>
									<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
										<td style="padding-right: 20px;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: right; width:100px">
											<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
											<span style="margin-left: 5px; vertical-align: top;"><?php esc_html_e( 'Desktop', 'wphb' ); ?></span>
										</td>
									<?php endif; ?>
									<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
										<td style="padding-right: 20px;border-radius: 0 4px 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: right; width: 100px">
											<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
											<span style="margin-left: 5px; vertical-align: top;"><?php esc_html_e( 'Mobile', 'wphb' ); ?></span>
										</td>
									<?php endif; ?>
								</tr>
								</thead>
								<tbody>
								<?php foreach ( $last_test->desktop->metrics as $index => $metric ) : ?>
									<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
											<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
												<?php echo esc_html( $metric->title ); ?>
											</span>
										</td>
										<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
											<td class="report-list-item-info" style="border-collapse: collapse !important;color: #888888;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: 600;letter-spacing: -0.25px;line-height: 21px;margin: 0;padding: 18px 0;text-align: right;vertical-align: top">
												<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px"><?php echo esc_html( $metric->displayValue ); ?></span>
												<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-' . Performance::get_impact_class( $metric->score * 100 ) . '.png' ); ?>" alt="<?php echo esc_attr( Performance::get_impact_class( $metric->score ) ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;">
											</td>
										<?php endif; ?>
										<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
											<td class="report-list-item-result ok" style="border-collapse: collapse !important;color: #888888;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: 600;letter-spacing: -0.25px;line-height: 21px;margin: 0;min-width: 65px;padding: 18px 0;text-align: right;vertical-align: top">
												<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px"><?php echo esc_html( $last_test->mobile->metrics->{$index}->displayValue ); ?></span>
												<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-' . Performance::get_impact_class( $last_test->mobile->metrics->{$index}->score * 100 ) . '.png' ); ?>" alt="<?php echo esc_attr( Performance::get_impact_class( $last_test->mobile->metrics->{$index}->score ) ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;">
											</td>
										<?php endif; ?>
									</tr>
								<?php endforeach; ?>
								</tbody>
							</table>
						<?php endif; ?>

						<?php if ( $params['SHOW_AUDITS'] ) : ?>
							<p style="color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 25px;font-weight: 600;line-height: 34px;margin: 0 0 5px;padding: 0;text-align: left;">
								<?php esc_html_e( 'Audits', 'wphb' ); ?>
							</p>

							<p style="color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 14px;letter-spacing: -0.23px;line-height: 22px;margin: 0 0 15px;padding: 0;text-align: left;">
								<?php esc_html_e( 'Audit results are divided into following three categories. Opportunities and Diagnostics provide recommendations to improve the performance score.', 'wphb' ); ?>
							</p>

							<table class="reports-list" align="center" style="border-collapse: collapse;border-spacing: 0;margin: 0 0 30px;padding: 0;text-align: left;vertical-align: top;width: 100%">
								<thead>
								<tr style="background-color: #F2F2F2">
									<td style="padding-left: 20px;border-radius: 4px 0 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: left">
										<?php esc_html_e( 'Categories', 'wphb' ); ?>
									</td>
									<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
										<td style="padding-right: 20px;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: right; width:100px">
											<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
											<span style="margin-left: 5px; vertical-align: top;"><?php esc_html_e( 'Desktop', 'wphb' ); ?></span>
										</td>
									<?php endif; ?>
									<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
										<td style="padding-right: 20px;border-radius: 0 4px 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: right; width: 100px">
											<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
											<span style="margin-left: 5px; vertical-align: top;"><?php esc_html_e( 'Mobile', 'wphb' ); ?></span>
										</td>
									<?php endif; ?>
								</tr>
								</thead>
								<tbody>
								<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
									<td class="report-list-item-info" style="border-collapse: collapse !important;color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
										<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
											<?php esc_html_e( 'Opportunities', 'wphb' ); ?>
										</span>
									</td>
									<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #ffffff;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: normal;line-height: 27px;margin: 0;padding: 15px 20px 15px 0;text-align: right;vertical-align: top">
											<?php
											$class = Performance::get_audits_class( $last_test->desktop->audits->opportunities );
											$color = '#1ABC9C';
											if ( 'error' === $class ) {
												$color = '#FF6D6D';
											} elseif ( 'warning' === $class ) {
												$color = '#FECF2F';
											}
											$opportunities = ! is_null( $last_test->desktop->audits->opportunities ) ? count( get_object_vars( $last_test->desktop->audits->opportunities ) ) : '-';
											?>
											<span style="color: inherit; display: inline-block; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;background-color: <?php echo esc_attr( $color ); ?>;width: 39px;height: 26px;border-radius: 13px;text-align: center;"><?php echo $opportunities; ?></span>
										</td>
									<?php endif; ?>
									<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #ffffff;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: normal;line-height: 27px;margin: 0;padding: 15px 20px 15px 0;text-align: right;vertical-align: top">
											<?php
											$class = Performance::get_audits_class( $last_test->mobile->audits->opportunities );
											$color = '#1ABC9C';
											if ( 'error' === $class ) {
												$color = '#FF6D6D';
											} elseif ( 'warning' === $class ) {
												$color = '#FECF2F';
											}
											$opportunities = ! is_null( $last_test->mobile->audits->opportunities ) ? count( get_object_vars( $last_test->mobile->audits->opportunities ) ) : '-';
											?>
											<span style="color: inherit; display: inline-block; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;background-color: <?php echo esc_attr( $color ); ?>;width: 39px;height: 26px;border-radius: 13px;text-align: center;"><?php echo $opportunities; ?></span>
										</td>
									<?php endif; ?>
								</tr>
								<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
									<td class="report-list-item-info" style="border-collapse: collapse !important;color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
										<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
											<?php esc_html_e( 'Diagnostics', 'wphb' ); ?>
										</span>
									</td>
									<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #ffffff;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: normal;line-height: 27px;margin: 0;padding: 15px 20px 15px 0;text-align: right;vertical-align: top">
											<?php
											$class = Performance::get_audits_class( $last_test->desktop->audits->diagnostics );
											$color = '#1ABC9C';
											if ( 'error' === $class ) {
												$color = '#FF6D6D';
											} elseif ( 'warning' === $class ) {
												$color = '#FECF2F';
											}
											$diagnostics = ! is_null( $last_test->desktop->audits->diagnostics ) ? count( get_object_vars( $last_test->desktop->audits->diagnostics ) ) : '-';
											?>
											<span style="color: inherit; display: inline-block; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;background-color: <?php echo esc_attr( $color ); ?>;width: 39px;height: 26px;border-radius: 13px;text-align: center;"><?php echo esc_html( $diagnostics ); ?></span>
										</td>
									<?php endif; ?>
									<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #ffffff;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: normal;line-height: 27px;margin: 0;padding: 15px 20px 15px 0;text-align: right;vertical-align: top">
											<?php
											$class = Performance::get_audits_class( $last_test->mobile->audits->diagnostics );
											$color = '#1ABC9C';
											if ( 'error' === $class ) {
												$color = '#FF6D6D';
											} elseif ( 'warning' === $class ) {
												$color = '#FECF2F';
											}
											$diagnostics = ! is_null( $last_test->mobile->audits->diagnostics ) ? count( get_object_vars( $last_test->mobile->audits->diagnostics ) ) : '-';
											?>
											<span style="color: inherit; display: inline-block; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;background-color: <?php echo esc_attr( $color ); ?>;width: 39px;height: 26px;border-radius: 13px;text-align: center;"><?php echo esc_html( $diagnostics ); ?></span>
										</td>
									<?php endif; ?>
								</tr>
								<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
									<td class="report-list-item-info" style="border-collapse: collapse !important;color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
										<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
											<?php esc_html_e( 'Passed Audits', 'wphb' ); ?>
										</span>
									</td>
									<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
										<?php $passed = ! is_null( $last_test->desktop->audits->passed ) ? count( get_object_vars( $last_test->desktop->audits->passed ) ) : '-'; ?>
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #ffffff;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: normal;line-height: 27px;margin: 0;padding: 15px 20px 15px 0;text-align: right;vertical-align: top">
											<span style="color: inherit; display: inline-block; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;background-color: #1ABC9C;width: 39px;height: 26px;border-radius: 13px;text-align: center;"><?php echo esc_html( $passed ); ?></span>
										</td>
									<?php endif; ?>
									<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
										<?php $passed = ! is_null( $last_test->mobile->audits->passed ) ? count( get_object_vars( $last_test->mobile->audits->passed ) ) : '-'; ?>
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #ffffff;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: normal;line-height: 27px;margin: 0;padding: 15px 20px 15px 0;text-align: right;vertical-align: top">
											<span style="color: inherit; display: inline-block; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;background-color: #1ABC9C;width: 39px;height: 26px;border-radius: 13px;text-align: center;"><?php echo esc_html( $passed ); ?></span>
										</td>
									<?php endif; ?>
								</tr>
								</tbody>
							</table>
						<?php endif; ?>

						<?php if ( $params['SHOW_HISTORIC'] ) : ?>
							<p style="color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 25px;font-weight: 600;line-height: 34px;margin: 0 0 5px;padding: 0;text-align: left;">
								<?php esc_html_e( 'Historic Field Data', 'wphb' ); ?>
							</p>

							<p style="color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 14px;letter-spacing: -0.23px;line-height: 22px;margin: 0 0 15px;padding: 0;text-align: left;">
								<?php
								printf(
									/* translators: %1$s - starting a tag, %2$s - closing a tag */
									esc_html__( 'We use %1$sChrome User Experience Report%2$s to generate insights about the real users’ experience with your webpage over the last 30 days.', 'wphb' ),
									'<a class="external" href="https://developers.google.com/web/tools/chrome-user-experience-report/" target="_blank" style="color: #17A8E3;font-family: \'Open Sans\', Arial, sans-serif;font-weight: inherit;line-height: 30px;margin: 0;padding: 0;text-align: left;text-decoration: none">',
									'</a>'
								);
								?>
							</p>

							<table class="reports-list" align="center" style="border-collapse: collapse;border-spacing: 0;margin: 0 0 30px;padding: 0;text-align: left;vertical-align: top;width: 100%">
								<?php if ( $last_test->desktop->field_data ) : ?>
									<thead>
									<tr style="background-color: #F2F2F2">
										<td style="padding-left: 20px;border-radius: 4px 0 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: left">
											<?php esc_html_e( 'Data', 'wphb' ); ?>
										</td>
										<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
											<td style="padding-right: 20px;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: right; width:100px">
												<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-desktop@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
												<span style="margin-left: 5px; vertical-align: top;"><?php esc_html_e( 'Desktop', 'wphb' ); ?></span>
											</td>
										<?php endif; ?>
										<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
											<td style="padding-right: 20px;border-radius: 0 4px 0 0;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;line-height: 27px; letter-spacing: -0.23px; text-align: right; width: 100px">
												<img alt="" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-mobile@2x.png' ); ?> 2x" style="height: 16px;vertical-align: middle;">
												<span style="margin-left: 5px; vertical-align: top;"><?php esc_html_e( 'Mobile', 'wphb' ); ?></span>
											</td>
										<?php endif; ?>
									</tr>
									</thead>
								<?php endif; ?>
								<tbody>
								<?php if ( ! $last_test->desktop->field_data ) : ?>
									<tr>
										<td colspan="3">
											<div style="border-radius: 4px;border: 1px solid #aaa;border-left: 2px solid #aaa;padding: 5px 15px">
												<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-notice.png' ); ?>" alt="<?php esc_attr_e( 'Warning', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: left; display: inline-block; margin: 15px 10px 0 10px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;">
												<p style="color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: normal;line-height: 22px;letter-spacing: -0.25px; margin-left:35px">
													<?php esc_html_e( 'The Chrome User Experience Report does not have sufficient real-world speed data for this page. Note: This report can take months to populate and is aimed at well established websites.', 'wphb' ); ?>
												</p>
											</div>
										</td>
									</tr>
								<?php else : ?>
									<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 600;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
											<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
												<?php esc_html_e( 'First Contentful Paint (FCP)', 'wphb' ); ?>
											</span>
										</td>
										<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
											<td class="report-list-item-info" style="border-collapse: collapse !important;color: #888888;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: 600;letter-spacing: -0.25px;line-height: 21px;margin: 0;padding: 18px 0;text-align: right;vertical-align: top">
												<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px">
													<?php
													/* translators: %s - number of seconds */
													printf( '%s s', esc_html( round( $last_test->desktop->field_data->FIRST_CONTENTFUL_PAINT_MS->percentile / 1000, 1 ) ) );
													?>
												</span>
												<?php if ( 'FAST' === $last_test->desktop->field_data->FIRST_CONTENTFUL_PAINT_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-success.png' ); ?>" alt="<?php esc_attr_e( 'Ok', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'AVERAGE' === $last_test->desktop->field_data->FIRST_CONTENTFUL_PAINT_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-warning.png' ); ?>" alt="<?php esc_attr_e( 'Warning', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'SLOW' === $last_test->desktop->field_data->FIRST_CONTENTFUL_PAINT_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-error.png' ); ?>" alt="<?php esc_attr_e( 'Critical', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php endif; ?>
											</td>
										<?php endif; ?>
										<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
											<td class="report-list-item-result ok" style="border-collapse: collapse !important;color: #888888;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: 600;letter-spacing: -0.25px;line-height: 21px;margin: 0;min-width: 65px;padding: 18px 0;text-align: right;vertical-align: top">
												<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px">
													<?php
													/* translators: %s - number of seconds */
													printf( '%s s', esc_html( round( $last_test->mobile->field_data->FIRST_CONTENTFUL_PAINT_MS->percentile / 1000, 1 ) ) );
													?>
												</span>
												<?php if ( 'FAST' === $last_test->mobile->field_data->FIRST_CONTENTFUL_PAINT_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-success.png' ); ?>" alt="<?php esc_attr_e( 'Ok', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'AVERAGE' === $last_test->mobile->field_data->FIRST_CONTENTFUL_PAINT_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-warning.png' ); ?>" alt="<?php esc_attr_e( 'Warning', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'SLOW' === $last_test->mobile->field_data->FIRST_CONTENTFUL_PAINT_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-error.png' ); ?>" alt="<?php esc_attr_e( 'Critical', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: right; display: inline-block; margin: 4px 20px 0 5px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php endif; ?>
											</td>
										<?php endif; ?>
									</tr>

									<tr class="report-list-item" style="padding: 0;text-align: left;vertical-align: top">
										<td class="report-list-item-info" style="border-collapse: collapse !important;color: #333333;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: bold;line-height: 22px;margin: 0;padding: 10px 0;text-align: left;vertical-align: top">
											<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;"><?php esc_html_e( 'First Input Delay (FID)', 'wphb' ); ?></span>
										</td>
										<?php if ( 'both' === $params['DEVICE'] || 'desktop' === $params['DEVICE'] ) : ?>
											<td class="report-list-item-info" style="border-collapse: collapse !important;color: #888888;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: normal;line-height: 22px;margin: 0;padding: 10px 0;text-align: right;vertical-align: top">
												<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px">
													<?php
													/* translators: %s - number of seconds */
													printf( '%s s', esc_html( $last_test->desktop->field_data->FIRST_INPUT_DELAY_MS->percentile ) );
													?>
												</span>
												<?php if ( 'FAST' === $last_test->desktop->field_data->FIRST_INPUT_DELAY_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-success.png' ); ?>" alt="<?php esc_attr_e( 'Ok', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: left; display: inline-block; margin: 5px 0 0 -20px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'AVERAGE' === $last_test->desktop->field_data->FIRST_INPUT_DELAY_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-warning.png' ); ?>" alt="<?php esc_attr_e( 'Warning', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: left; display: inline-block; margin: 5px 0 0 -20px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'SLOW' === $last_test->desktop->field_data->FIRST_INPUT_DELAY_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-error.png' ); ?>" alt="<?php esc_attr_e( 'Critical', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: left; display: inline-block; margin: 5px 0 0 -20px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php endif; ?>
											</td>
										<?php endif; ?>
										<?php if ( 'both' === $params['DEVICE'] || 'mobile' === $params['DEVICE'] ) : ?>
											<td class="report-list-item-result ok" style="border-collapse: collapse !important;color: #888888;font-family: 'Open Sans', Arial, sans-serif;font-size: 13px;font-weight: normal;line-height: 22px;margin: 0;min-width: 65px;padding: 10px 0;text-align: right;vertical-align: top">
												<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px">
													<?php
													/* translators: %s - number of seconds */
													printf( '%s s', esc_html( $last_test->mobile->field_data->FIRST_INPUT_DELAY_MS->percentile ) );
													?>
												</span>
												<?php if ( 'FAST' === $last_test->mobile->field_data->FIRST_INPUT_DELAY_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-success.png' ); ?>" alt="<?php esc_attr_e( 'Ok', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: left; display: inline-block; margin: 5px 0 0 -20px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'AVERAGE' === $last_test->mobile->field_data->FIRST_INPUT_DELAY_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-warning.png' ); ?>" alt="<?php esc_attr_e( 'Warning', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: left; display: inline-block; margin: 5px 0 0 -20px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php elseif ( 'SLOW' === $last_test->mobile->field_data->FIRST_INPUT_DELAY_MS->category ) : ?>
													<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/icon-error.png' ); ?>" alt="<?php esc_attr_e( 'Critical', 'wphb' ); ?>" style="-ms-interpolation-mode: bicubic; border: none; clear: both; float: left; display: inline-block; margin: 5px 0 0 -20px; height: 16px; outline: none; text-decoration: none; width: auto; vertical-align: middle;" />
												<?php endif; ?>
											</td>
										<?php endif; ?>
									</tr>
								<?php endif; ?>
								</tbody>
							</table>
						<?php endif; ?>

						<p style="color: #555555;font-family: 'Open Sans', Arial, sans-serif;font-size: 16px;font-weight: normal;line-height: 20px;margin: 0 0 20px;padding: 0;text-align: center">
							<a href="<?php echo esc_url( $params['FULL_REPORT_URL'] ); ?>" class="brand-button" style="background: #17A8E3;color: #ffffff;font-family: 'Open Sans', Arial, sans-serif;font-size: 16px;font-weight: normal;line-height: 20px;margin: 0;padding: 10px 20px;text-align: center;text-decoration: none;display: inline-block;border-radius: 4px;text-transform: uppercase">
								<?php esc_html_e( 'View full report', 'wphb' ); ?>
							</a>
						</p>

						<p style="margin: 0 0 30px;padding: 0;text-align: center">
							<a style="color: #17A8E3;font-family: 'Open Sans', Arial, sans-serif;font-size: 12px;font-weight: 500;letter-spacing: -0.25px;line-height: 16px;text-decoration: none" href="<?php echo esc_url( $params['NOTIFICATIONS_URL'] ); ?>" class="brand-link" target="_blank">
								<?php esc_html_e( 'Customize email report', 'wphb' ); ?>
							</a>
						</p>

						<p style="color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 15px;font-weight: normal;line-height: 20px;margin: 0 0 20px;padding: 0;text-align: left;clear: both"><?php esc_html_e( 'Stay humming.', 'wphb' ); ?></p>
						<strong><?php esc_html_e( 'Hummingbird', 'wphb' ); ?></strong>
						<p style="color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 15px;font-weight: normal;line-height: 15px;margin: 10px 0 15px;padding: 0;text-align: left"><?php esc_html_e( 'Performance Hero', 'wphb' ); ?></p>
						<p style="color: #666666;font-family: 'Open Sans', Arial, sans-serif;font-size: 15px;font-weight: normal;line-height: 15px;margin: 0 0 30px;padding: 0;text-align: left"><?php esc_html_e( 'WPMU DEV', 'wphb' ); ?></p>
					</td>
				</tr>
				</tbody>
			</table>

		</td>
	</tr>
	</tbody>
</table>