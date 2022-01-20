<?php
/**
 * Index file.
 *
 * @package Hummingbird
 *
 * @var Object $last_test  Last test data.
 * @var array  $params     Parameters array.
 */

use Hummingbird\Core\Pro\Modules\Reports;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$alt_text = sprintf( /* translators: %s - report type */
	__( 'Hummingbird %s Report', 'wphb' ),
	ucfirst( $params['REPORT_TYPE'] )
);

$args = compact( 'last_test', 'params' );
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
	<title><?php echo esc_html( $alt_text ); ?></title>
	<!--[if !mso]><!-->
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<!--<![endif]-->
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style type="text/css">
		#outlook a {
			padding: 0;
		}

		body {
			margin: 0;
			padding: 0;
			-webkit-text-size-adjust: 100%;
			-ms-text-size-adjust: 100%;
		}

		table,
		td {
			border-collapse: collapse;
			mso-table-lspace: 0pt;
			mso-table-rspace: 0pt;
		}

		img {
			border: 0;
			height: auto;
			line-height: 100%;
			outline: none;
			text-decoration: none;
			-ms-interpolation-mode: bicubic;
		}

		p {
			display: block;
			margin: 13px 0;
		}
	</style>
	<!--[if mso]>
	<xml>
		<o:OfficeDocumentSettings>
			<o:AllowPNG/>
			<o:PixelsPerInch>96</o:PixelsPerInch>
		</o:OfficeDocumentSettings>
	</xml>
	<![endif]-->
	<!--[if lte mso 11]>
	<style type="text/css">
		.mj-outlook-group-fix { width:100% !important; }
	</style>
	<![endif]-->
	<!--[if !mso]><!-->
	<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" type="text/css">
	<style type="text/css">
		@import url(https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap);
	</style>
	<!--<![endif]-->
	<style type="text/css">
		@media only screen and (min-width:480px) {
			.mj-column-per-100 {
				width: 100% !important;
				max-width: 100%;
			}
		}
	</style>
	<style type="text/css">
		@media only screen and (max-width:480px) {
			table.mj-full-width-mobile {
				width: 100% !important;
			}

			td.mj-full-width-mobile {
				width: auto !important;
			}
			table.reports-list tr > td:first-child {
				width: 45%;
			}
		}
	</style>
	<style type="text/css">
		* {
			-webkit-font-smoothing: antialiased;
			-moz-osx-font-smoothing: grayscale;
		}

		.p-30 {
			margin-bottom: 30px !important;
		}

		h1 {
			font-size: 25px;
			line-height: 35px;
		}

		h2 {
			font-size: 20px;
			line-height: 30px;
		}

		p,
		li {
			font-size: 14px;
			line-height: 30px;
		}

		a {
			text-decoration: none !important;
			font-weight: 600 !important;
			color: #286EFA !important;
		}

		.hidden-img img {
			display: none !important;
		}

		.button a,
		a.button,
		a.button-cta {
			font-family: Roboto, arial, sans-serif;
			font-size: 13px !important;
			line-height: 20px;
			font-weight: bold;
			background: #286EFA;
			text-decoration: none !important;
			padding: 10px 15px;
			color: #ffffff !important;
			border-radius: 10px;
			display: inline-block;
			margin: 20px auto;
			text-transform: unset !important;
			min-width: unset !important;
		}

		small {
			font-size: 10px;
			line-height: 24px;
		}

		.main-content img {
			max-width: 100% !important;
		}

		@media (min-width: 600px) {
			p,
			li {
				font-size: 16px;
			}
		}
	</style>
</head>

<body style="word-spacing:normal;background-color:#F6F6F6;">
<div style="background-color:#F6F6F6;">
	<?php Reports::load_template( 'header', $args ); ?>

	<!-- Main content -->
	<!--[if mso | IE]><table align="center" border="0" cellpadding="0" cellspacing="0" class="main-content-outlook" style="width:600px;" width="600" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
	<div class="main-content" style="background:#ffffff;background-color:#ffffff;margin:0px auto;max-width:600px;">
		<table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation" style="background:#ffffff;background-color:#ffffff;width:100%;">
			<tbody>
			<tr>
				<td style="direction:ltr;font-size:0px;padding:30px 25px 45px;text-align:center;">
					<!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:550px;" ><![endif]-->
					<div class="mj-column-per-100 mj-outlook-group-fix" style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;width:100%;">
						<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="vertical-align:top;" width="100%">
							<tbody>
							<tr>
								<td align="left" style="font-size:0px;padding:0;word-break:break-word;">
									<div style="font-family:Roboto, Arial, sans-serif;font-size:18px;letter-spacing:-.25px;line-height:30px;text-align:left;color:#1A1A1A;">
										<?php Reports::load_template( $params['REPORT_TYPE'], $args ); ?>
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

	<?php Reports::load_template( 'footer', $args ); ?>
</div>
</body>

</html>