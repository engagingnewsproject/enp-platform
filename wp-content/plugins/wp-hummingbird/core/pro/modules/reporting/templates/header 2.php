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
<link href="https://fonts.googleapis.com/css?family=Open+Sans:400,600&display=swap" rel="stylesheet">
<table class="wrapper hero" align="left" style="background-color: #e9ebe7; border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
	<tbody>
	<tr style="padding: 0; text-align: left; vertical-align: top;">
		<td class="wrapper-inner hero-inner" style="-moz-hyphens: auto; -webkit-hyphens: auto; border-collapse: collapse !important; color: #555555; font-family: 'Open Sans', Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 20px 0 0; text-align: left; vertical-align: top; word-wrap: break-word;">

			<table class="hero-content" align="left" style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top; width: 100%;">
				<tbody>
				<tr style="padding: 0; text-align: center; vertical-align: bottom;">
					<td class="hero-image" style="background-color: #888888; border-radius: 4px 4px 0 0; height: 150px; border-collapse: collapse !important; margin: 0; padding: 0; text-align: center; vertical-align: bottom;">
						<img src="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/header@2x.png' ); ?>" srcset="<?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/header.png' ); ?>, <?php echo esc_url( WPHB_DIR_URL . 'core/pro/modules/reporting/templates/images/header@2x.png' ); ?> 2x" alt="<?php echo esc_attr( $alt_text ); ?>" style="-ms-interpolation-mode: bicubic; border: none; vertical-align:bottom; clear: both; display: inline-block; outline: none; text-decoration: none; width: auto; height: 147px">
					</td>
				</tr>
				</tbody>
			</table>
		</td>
	</tr>
	</tbody>
</table>