<?php
/**
 * This template is used to display emails.
 *
 * @package WP_Defender
 */

 /**
  * WPCS errors in this page are,
  * 1) Stylesheets must be registered/enqueued via wp_enqueue_style()
  * @codingStandardsIgnoreFile
  */
use WP_Defender\Integrations\Dashboard_Whitelabel;

$dashboard_whitelabel = wd_di()->get( Dashboard_Whitelabel::class );
$can_whitelabel       = $dashboard_whitelabel->can_whitelabel();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<meta name="viewport" content="width=device-width">
	<title><?php echo esc_html( $title ); ?></title>
	<!--[if lte mso 11]>
	<style type="text/css">
		.mj-outlook-group-fix {
			width: 100% !important;
		}
	</style>
	<![endif]-->
	<!--[if !mso]><!-->
	<link href="https://fonts.bunny.net/css?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" type="text/css">
	<style type="text/css">
		@import url(https://fonts.bunny.net/css?family=Roboto:wght@400;500;700&display=swap);
	</style>
	<!--<![endif]-->
	<style type="text/css">
		@media only screen {
			html {
				min-height: 100%;
			}
		}

		a {
			text-decoration: none !important;
			font-weight: 600 !important;
			color: #0059FF !important;
		}

		.report-list-item-path span {
			color: #0059FF;
		}

		.button a,
		a.view-full,
		a.button-cta {
			font-family: Roboto, arial, sans-serif;
			font-size: 13px !important;
			line-height: 24px;
			font-weight: bold;
			background: #0059FF;
			text-decoration: none !important;
			padding: 10px 15px;
			color: #ffffff !important;
			border-radius: 6px;
			display: inline-block;
			margin: 20px auto;
			text-transform: unset !important;
			min-width: unset !important;
		}

		/* OTP style */
		span.defender-otp {
			background: #F2F2F2;
			padding: 20px;
			border-radius: 10px;
			display: block;
			text-align: center;
			font-weight: 500;
			font-size: 16px;
			line-height: 22px;
		}

		/* Hover-cases: */
		/* 1.All links. */
		.main-content a:hover {
			color: #0C33A9 !important;
		}

		/* 2.Link for full view. */
		.main-content a.view-full:hover {
			background: #0C33A9 !important;
			color: #ffffff !important;
		}

		/* 3.Text. */
		.report-list-item-path span:hover {
			color: #0C33A9 !important;
		}

		@media only screen and (min-width: 480px) {
			.mj-column-per-100 {
				width: 100% !important;
				max-width: 100%;
			}
		}
	</style>
</head>

<body
		style="-moz-box-sizing: border-box; -ms-text-size-adjust: 100%; -webkit-box-sizing: border-box; -webkit-text-size-adjust: 100%; background-color: #F2F2F2; box-sizing: border-box; color: #1A1A1A; font-family: Roboto, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; min-width: 100%; padding: 0; text-align: left; width: 100% !important;<?php echo $can_whitelabel ? 'padding-top:45px;' : ''; ?>">
<!-- Header image -->
<?php if ( ! $can_whitelabel ) : ?>
	<!--[if mso | IE]>
	<table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600">
		<tr>
			<td style="line-height:0;font-size:0;mso-line-height-rule:exactly;"><![endif]-->
	<div style="margin:0 auto;max-width:600px;">
		<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
			<tbody>
			<tr>
				<td style="direction:ltr;font-size:0;padding:25px 0 0;text-align:center;">
					<!--[if mso | IE]>
					<table role="presentation" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td class="" style="vertical-align:top;width:600px;"><![endif]-->
					<div class="mj-column-per-100 mj-outlook-group-fix"
						style="font-size:0;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
						<table border="0" cellpadding="0" cellspacing="0" role="presentation" width="100%">
							<tbody>
							<tr>
								<td style="background-color:#282A2C;border-radius:15px 15px 0 0;vertical-align:top;padding:35px 0;">
									<table border="0" cellpadding="0" cellspacing="0" role="presentation" style=""
											width="100%">
										<tbody>
										<tr>
											<td align="center" style="font-size:0;padding:0;word-break:break-word;">
												<table border="0" cellpadding="0" cellspacing="0" role="presentation"
														style="border-collapse:collapse;border-spacing:0;">
													<tbody>
													<tr>
														<td>
															<img height="30" width="27"
																src="<?php echo esc_url( $dashboard_whitelabel->get_branding_logo() ); ?>"
																style="border:0;outline:none;text-decoration:none;height:30px;width:27px;vertical-align:middle;"
																alt="<?php echo esc_attr( $title ); ?>">
															<span style="color: #FFFFFF;font-family: Roboto, Arial, sans-serif;font-size: 20px;font-weight: 700;text-align: left;margin-left: 10px;line-height:25px; vertical-align:middle;">
																		<?php echo esc_html( $title ); ?>
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
<?php endif; ?>
<!-- END Header image -->

<!-- Main content -->
<!--[if mso | IE]>
<table align="center" border="0" cellpadding="0" cellspacing="0" class="main-content-outlook" style="width:600px;"
		width="600">
	<tr>
		<td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
<div class="main-content"
	style="background:#ffffff;background-color:#ffffff;margin:0px auto;max-width:600px;<?php echo $can_whitelabel ? 'border-radius:20px;overflow:hidden;' : ''; ?>">
	<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
			style="background:#ffffff;background-color:#ffffff;width:100%;">
		<tbody>
		<tr>
			<td style="direction:ltr;font-size:0px;padding:30px 25px 45px;text-align:center;">
				<!--[if mso | IE]>
				<table role="presentation" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td class="" style="vertical-align:top;width:550px;"><![endif]-->
				<div class="mj-column-per-100 mj-outlook-group-fix"
					style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
					<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;"
							width="100%">
						<tbody>
						<tr>
							<td align="left" style="font-size:0px;padding:0;word-break:break-word;">
								<div style="font-family:Roboto, Arial, sans-serif;font-size:18px;letter-spacing:-.25px;line-height:30px;text-align:left;color:#1A1A1A;">
									<?php echo wp_kses_post( $content_body ); ?>
									<!--Cheers block-->
									<?php
									if (
										! $can_whitelabel ||
										( $dashboard_whitelabel->is_change_footer() && $dashboard_whitelabel->is_set_footer_text() )
									) :
										?>
										<p style="color:#1A1A1A;font-family:Roboto,Arial,sans-serif;font-size:16px;font-weight:normal;line-height:30px;margin:30px 0 0;padding:0;text-align:left;">
											<?php esc_html_e( 'Cheers,', 'wpdef' ); ?>
											<br/>
											<?php echo esc_html( $dashboard_whitelabel->get_footer_text() ); ?>
										</p>
									<?php endif; ?>
									<!--End Cheers block-->
								</div>
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
<!-- END Main content -->

<!-- Footer -->
<?php if ( ! $can_whitelabel ) : ?>
	<!--[if mso | IE]>
	<table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600">
		<tr>
			<td style="line-height:0;font-size:0;mso-line-height-rule:exactly;"><![endif]-->
	<div style="background:#E7F1FB;background-color:#E7F1FB;margin:0 auto;border-radius:0 0 15px 15px;max-width:600px;">
		<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
				style="background:#E7F1FB;background-color:#E7F1FB;width:100%;border-radius:0 0 15px 15px;">
			<tbody>
			<tr>
				<td style="direction:ltr;font-size:0;padding:20px 0;text-align:center;">
					<!--[if mso | IE]>
					<table role="presentation" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td class="" style="vertical-align:top;width:600px;"><![endif]-->
					<div class="mj-column-per-100 mj-outlook-group-fix"
						style="font-size:0;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
						<table border="0" cellpadding="0" cellspacing="0" role="presentation"
								style="vertical-align:top;" width="100%">
							<tbody>
							<tr>
								<td align="center" style="font-size:0;padding:10px 25px;word-break:break-word;">
									<table border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="border-collapse:collapse;border-spacing:0;">
										<tbody>
										<tr>
											<td style="width:168px;">
												<img height="30" width="170"
													src="<?php echo esc_url( defender_asset_url( '/assets/email-images/wpmudev-logo@2x.png' ) ); ?>"
													style="border:0;display:block;outline:none;text-decoration:none;height:30px;width:170px;font-size:13px;"/>
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
	<!--[if mso | IE]></td></tr></table>
	<table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600">
		<tr>
			<td style="line-height:0;font-size:0;mso-line-height-rule:exactly;"><![endif]-->
	<div style="margin:0 auto;max-width:600px;">
		<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
			<tbody>
			<tr>
				<td style="direction:ltr;font-size:0;padding:25px 20px 15px;text-align:center;">
					<!--[if mso | IE]>
					<table role="presentation" border="0" cellpadding="0" cellspacing="0">
						<tr>
							<td class="" style="vertical-align:top;width:560px;"><![endif]-->
					<div class="mj-column-per-100 mj-outlook-group-fix"
						style="font-size:0;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
						<table border="0" cellpadding="0" cellspacing="0" role="presentation"
								style="vertical-align:top; line-height:normal;" width="100%">
							<tbody>
							<tr>
								<td align="center" style="font-size:0;padding:0;word-break:break-word;">
									<!--[if mso | IE]>
									<table align="center" border="0" cellpadding="0" cellspacing="0"
											role="presentation">
										<tr>
											<td><![endif]-->
									<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="float:none;display:inline-table;">
										<tr class="hidden-img">
											<td style="padding:1px;vertical-align:middle;">
												<table border="0" cellpadding="0" cellspacing="0" role="presentation"
														style="background:transparent;border-radius:3px;width:0;">
													<tr>
														<td style="font-size:0;height:0;vertical-align:middle;width:0;">
															<img height="0" style="border-radius:3px;display:block;"
																width="0"/>
														</td>
													</tr>
												</table>
											</td>
											<td style="vertical-align:middle;">
												<span style="color:#333333;font-size:13px;font-weight:700;font-family:Roboto, Arial, sans-serif;line-height:25px;text-decoration:none;">
												<?php
												esc_html_e(
													'Follow us',
													'wpdef'
												);
												?>
														</span>
											</td>
										</tr>
									</table>
									<!--[if mso | IE]></td>
									<td><![endif]-->
									<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="float:none;display:inline-table;">
										<tr>
											<td style="padding:1px;vertical-align:middle;">
												<table border="0" cellpadding="0" cellspacing="0" role="presentation"
														style="background:transparent;border-radius:3px;width:25px;">
													<tr>
														<td style="font-size:0;height:25px;vertical-align:middle;text-align:center;width:25px;">
															<a href="https://www.facebook.com/wpmudev" target="_blank"
																style="display:inline-block;">
																<img height="14" width="7"
																	src="<?php echo esc_url( defender_asset_url( '/assets/email-images/icon-fb.png' ) ); ?>"
																	style="width:7px;height:14px;border-radius:3px;display:block;"/>
															</a>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<!--[if mso | IE]></td>
									<td><![endif]-->
									<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="float:none;display:inline-table;">
										<tr>
											<td style="padding:1px;vertical-align:middle;">
												<table border="0" cellpadding="0" cellspacing="0" role="presentation"
														style="background:transparent;border-radius:3px;width:25px;">
													<tr>
														<td style="font-size:0;height:25px;vertical-align:middle;text-align:center;width:25px;">
															<a href="https://www.instagram.com/wpmu_dev/"
																target="_blank" style="display:inline-block;">
																<img height="14" width="14"
																	src="<?php echo esc_url( defender_asset_url( '/assets/email-images/icon-instagram.png' ) ); ?>"
																	style="width:14px;height:14px;border-radius:3px;display:block;"/>
															</a>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<!--[if mso | IE]></td>
									<td><![endif]-->
									<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
											style="float:none;display:inline-table;">
										<tr>
											<td style="padding:1px;vertical-align:middle;">
												<table border="0" cellpadding="0" cellspacing="0" role="presentation"
														style="background:transparent;border-radius:3px;width:25px;">
													<tr>
														<td style="font-size:0;height:25px;vertical-align:middle;text-align:center;width:25px;">
															<a href="https://twitter.com/wpmudev" target="_blank"
																style="display:inline-block;">
																<img height="12" width="13"
																	src="<?php echo esc_url( defender_asset_url( '/assets/email-images/icon-twitter.png' ) ); ?>"
																	style="width:13px;height:12px;border-radius:3px;display:block;"/>
															</a>
														</td>
													</tr>
												</table>
											</td>
										</tr>
									</table>
									<!--[if mso | IE]></td></tr></table><![endif]-->
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
<?php endif; ?>
<!--[if mso | IE]></td></tr></table>
<table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600">
	<tr>
		<td style="line-height:0;font-size:0;mso-line-height-rule:exactly;"><![endif]-->
<div style="margin:0 auto;max-width:600px;">
	<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="width:100%;">
		<tbody>
		<tr>
			<td style="direction:ltr;font-size:0;padding:0;text-align:center;">
				<!--[if mso | IE]>
				<table role="presentation" border="0" cellpadding="0" cellspacing="0">
					<tr>
						<td class="" style="vertical-align:top;width:600px;"><![endif]-->
				<div class="mj-column-per-100 mj-outlook-group-fix"
					style="font-size:0;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
					<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;"
							width="100%">
						<tbody>
						<tr>
							<td align="center" style="font-size:0;padding:0 0 15px;word-break:break-word;">
								<?php if ( ! $can_whitelabel ) : ?>
									<div style="font-family:Roboto, Arial, sans-serif;font-size:10px;letter-spacing:-.25px;line-height:30px;text-align:center;color:#505050;">
										INCSUB PO BOX 163, ALBERT PARK, VICTORIA.3206 AUSTRALIA
									</div>
								<?php endif; ?>
								<!-- Unsubscribe section -->
								<?php if ( ! empty( $unsubscribe_link ) ) : ?>
									<div style="font-family:Roboto, Arial, sans-serif;font-size:10px;letter-spacing:-.25px;line-height:30px;text-align:center;color:#505050;">
										<a href="<?php echo esc_url( $unsubscribe_link ); ?>"
											style="color: #000!important;text-decoration-line:underline!important;font-weight: 400!important;">
											<?php
											esc_html_e(
												'Unsubscribe',
												'wpdef'
											);
											?>
										</a>
									</div>
								<?php endif; ?>
								<!-- End Unsubscribe -->
							</td>
						</tr>
						<tr>
							<td align="center" style="font-size:0;padding:0 0 25px;word-break:break-word;">
								<div style="font-family:Roboto, Arial, sans-serif;font-size:10px;letter-spacing:-.25px;line-height:30px;text-align:center;color:#1A1A1A;"></div>
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
<!-- END footer -->
</body>
</html>