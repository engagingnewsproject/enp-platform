<?php
/**
 * This template is used to generate an email for the firewalls report.
 *
 * @package WP_Defender
 */

?>
<table class="main-intro"
		style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
	<tbody>
	<tr style="padding: 0; text-align: left; vertical-align: top;">
		<td class="main-intro-content"
			style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #1a1a1a; font-size: 16px; font-weight: normal; hyphens: auto; line-height: 24px; margin: 0; padding: 0; text-align: left; vertical-align: top; word-wrap: break-word;">
			<?php if ( 0 === $count_total ) { ?>
				<p style="-webkit-font-smoothing:antialiased;font-size:16px;font-smoothing:antialiased;font-weight:normal;line-height:24px;margin:0;margin-bottom:30px;padding:0;text-align:left">
					<?php
					/* translators: %s: Name. */
					printf( esc_html__( 'Hi %s,', 'wpdef' ), esc_html( $name ) );
					?>
				</p>
				<p style="font-size:16px;font-weight:normal;line-height:24px;margin:0;padding: 0 0 30px;text-align:left;">
					<?php
					/* translators: %s - site URL, %s - time unit. */
					printf(
					/* translators: 1: Site URL, 2: Time unit. */
						esc_html__( 'There were no lockouts for %1$s %2$s.', 'wpdef' ),
						esc_url( $site_url ),
						esc_html( $time_unit )
					);
					?>
				</p>
			<?php } else { ?>
				<h1 style="-webkit-font-smoothing:antialiased;color:inherit;font-size:25px;font-smoothing:antialiased;font-weight:bold;line-height:30px;margin:0 0 20px;padding:0;text-align:left;word-wrap:normal">
					<?php
					/* translators: %s: Site URL. */
					printf( esc_html__( 'Firewall report for %s', 'wpdef' ), esc_url( $site_url ) );
					?>
				</h1>
				<p style="-webkit-font-smoothing:antialiased;font-size:16px;font-smoothing:antialiased;font-weight:normal;line-height:24px;margin:0;margin-bottom:30px;padding:0;text-align:left">
					<?php
					/* translators: %s: Name. */
					printf( esc_html__( 'Hi %s,', 'wpdef' ), esc_html( $name ) );
					?>
				</p>
				<p style="font-size:16px;font-weight:normal;line-height:24px;margin:0;padding: 0 0 30px;text-align:left;">
					<?php
					$count_total_text = sprintf(
					/* translators: %d: Count. */
						_n(
							'%d host has recently been locked out for suspicious behavior.',
							'%d hosts have recently been locked out for suspicious behavior.',
							$count_total,
							'wpdef'
						),
						number_format_i18n( $count_total )
					);
					/* translators: %s: Total text. */
					printf( esc_html__( '%s See details below.', 'wpdef' ), esc_html( $count_total_text ) );
					?>
				</p>
			<?php } ?>
		</td>
	</tr>
	</tbody>
</table>
<?php if ( 0 !== $count_total ) { ?>
	<!-- Main content -->
	<table class="reports-list" align="center"
			style="border-collapse: collapse;border-spacing: 0;margin: 0 0 30px;padding: 0;text-align: left;vertical-align: top;width: 100%">
		<thead>
		<tr style="background-color: #F2F2F2">
			<td style="padding-left: 20px;border-radius: 4px 0 0 0;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;line-height: 27px; letter-spacing: -0.23px; text-align: left">
				<?php esc_html_e( 'Lockout type', 'wpdef' ); ?>
			</td>
			<td style="padding-right: 20px;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;line-height: 27px; letter-spacing: -0.23px; text-align: right; width:100px">
				<?php esc_html_e( 'Total Lockouts', 'wpdef' ); ?>
			</td>
		</tr>
		</thead>
		<tbody>
		<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
					<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
						<?php esc_html_e( '404 Lockout', 'wpdef' ); ?>
					</span>
			</td>
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 13px;font-weight: 500;letter-spacing: -0.25px;line-height: 21px;margin: 0;min-width: 65px;padding: 18px 0;text-align: right;vertical-align: top">
				<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;padding-right: 20px;"><?php echo esc_html( $count_lockouts['404'] ); ?></span>
			</td>
		</tr>
		<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
					<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
						<?php esc_html_e( 'Login Lockout', 'wpdef' ); ?>
					</span>
			</td>
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 13px;font-weight: 500;letter-spacing: -0.25px;line-height: 21px;margin: 0;min-width: 65px;padding: 18px 0;text-align: right;vertical-align: top">
				<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;padding-right: 20px;"><?php echo esc_html( $count_lockouts['login'] ); ?></span>
			</td>
		</tr>
		<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;letter-spacing: -0.23px;line-height: 22px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
					<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle; letter-spacing: -0.25px;padding-left: 20px;">
						<?php esc_html_e( 'User Agent Lockout', 'wpdef' ); ?>
					</span>
			</td>
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 13px;font-weight: 500;letter-spacing: -0.25px;line-height: 21px;margin: 0;min-width: 65px;padding: 18px 0;text-align: right;vertical-align: top">
				<span style="color: inherit; display: inline; font-size: inherit; font-family: inherit; line-height: inherit; vertical-align: middle;letter-spacing: -0.25px;padding-right: 20px;"><?php echo esc_html( $count_lockouts['ua'] ); ?></span>
			</td>
		</tr>
		</tbody>
	</table>
	<!-- End Main content -->
<?php } ?>
<table class="row"
		style="border-collapse:collapse;border-spacing:0;display:table;padding:0;position:relative;text-align:left;vertical-align:top;width:100%">
	<tbody>
	<tr style="padding:0;text-align:left;vertical-align:top">
		<th class="small-12 large-12 columns first last"
			style="color:#000;font-size:18px;font-weight:400;line-height:25px;margin:0 auto;padding:15px 15px 0;text-align:left;width:585px">
			<table style="border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:100%">
				<tr style="padding:0;text-align:left;vertical-align:top">
					<th style="color:#000;font-size:18px;font-weight:400;line-height:25px;margin:0;padding:0;text-align:left">
						<table class="button btn-center"
								style="margin:0 auto;border-collapse:collapse;border-spacing:0;padding:0;text-align:left;vertical-align:top;width:auto">
							<tr style="padding:0;text-align:left;vertical-align:top">
								<td style="-moz-hyphens:auto;-webkit-hyphens:auto;border-collapse:collapse!important;color:#000;font-size:18px;font-weight:400;hyphens:auto;line-height:25px;margin:0;padding:0;text-align:left;vertical-align:top;word-wrap:break-word">
									<table style="border-collapse:collapse;border-radius:4px;border-spacing:0;overflow:hidden;padding:0;text-align:left;vertical-align:top;width:100%">
										<tr style="padding:0;text-align:left;vertical-align:top">
											<td style="-moz-hyphens:auto;-webkit-hyphens:auto;background:#0059FF;border:none;border-collapse:collapse!important;color:#fff;font-size:18px;font-weight:400;hyphens:auto;line-height:25px;margin:0;padding:0;text-align:center;vertical-align:top;word-wrap:break-word">
												<a href="<?php echo esc_url( $logs_url ); ?>" class="button view-full"
													style="border:0 solid #0059FF;border-radius:4px;color:#fff;display:inline-block;font-size:15px;font-weight:400;line-height:25px;margin:0;min-width:275px;padding:8px 16px 8px 16px;text-align:center;text-decoration:none;text-transform:uppercase"
												>
													<?php esc_html_e( 'View Full Logs', 'wpdef' ); ?>
												</a>
											</td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</th>
				</tr>
			</table>
		</th>
	</tr>
	</tbody>
</table>