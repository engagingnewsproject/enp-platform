<?php
/**
 * This template is used to display an issue in the email.
 *
 * @package WP_Defender
 */

?>
<tr style="border:none;padding:0;text-align:left;vertical-align:top">
	<td style="-moz-hyphens:auto;-webkit-hyphens:auto;border-collapse:collapse!important;border-bottom:.5px solid #d8d8d8;color:#333;font-size:16px;font-weight:500;letter-spacing: -0.31px;hyphens:auto;line-height:22px;margin:0;padding:10px 15px;text-align:left;vertical-align:top;word-wrap:break-word">
		<img src="<?php echo esc_url( $status_img ); ?>"
			alt="<?php echo esc_attr__( 'Hero Image', 'wpdef' ); ?>"
			style="-ms-interpolation-mode:bicubic;clear:both;display:inline-block;margin-right:10px;max-width:100%;outline:0;text-decoration:none;vertical-align:middle;width:18px"
		/>
		<?php echo esc_html( $tweak_title ); ?>
		<span style="width:100%;color: #888888;padding-left: 32px;font-size: 13px;font-weight:400;letter-spacing: -0.25px;line-height: 22px;display: block">
			<?php echo esc_html( $error_reason ); ?>
		</span>
	</td>
</tr>