<?php
/**
 * Header file.
 *
 * @package Hummingbird
 * @var array $params
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alt_text = sprintf(
	/* translators: %s - report type */
	__( 'Hummingbird %s Report', 'wphb' ),
	ucfirst( $params['REPORT_TYPE'] )
);

?>

<!-- Header image -->
<!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
<div style="margin:0px auto;max-width:600px;">
	<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
		<tbody>
		<tr>
			<td style="direction:ltr;font-size:0px;padding:25px 0 0;text-align:center;">
				<!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:600px;" ><![endif]-->
				<div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
					<table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
						<tbody>
						<tr>
							<td style="background-color:#FF8E3C;border-radius:15px 15px 0 0;vertical-align:top;padding:35px 0;">
								<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="" width="100%">
									<tbody>
									<tr>
										<td align="center" style="font-size:0px;padding:10px 25px;word-break:break-word;">
											<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="border-collapse:collapse;border-spacing:0px;">
												<tbody>
												<tr>
													<td>
														<img height="25" src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/logo@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/logo.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/logo@2x.png' ); ?> 2x" style="border:0;outline:none;text-decoration:none;height:25px;width:30px;vertical-align:bottom;" width="30" alt="<?php echo esc_attr( $alt_text ); ?>">
														<span style="color: #FFFFFF;font-family: Roboto, Arial, sans-serif;font-size: 20px;font-weight: 700;text-align: left;margin-left: 10px;line-height:25px">
															<?php
															switch ( $params['REPORT_TYPE'] ) {
																case 'performance':
																default:
																	esc_html_e( 'Performance Test', 'wphb' );
																	break;
																case 'uptime':
																	esc_html_e( 'Uptime', 'wphb' );
																	break;
																case 'database':
																	esc_html_e( 'Database Cleanup', 'wphb' );
																	break;
															}
															?>
														</span>
													</td>
												</tr>
												</tbody>
											</table>
										</td>
									</tr>
									</tbody>
								</table>
							</td>
						</tr>
						</tbody>
					</table>
				</div>
				<!--[if mso | IE]></td></tr></table><![endif]-->
			</td>
		</tr>
		</tbody>
	</table>
</div>
<!--[if mso | IE]></td></tr></table><![endif]-->
<!-- END Header image -->