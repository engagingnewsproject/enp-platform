<?php
/**
 * This template is used to display list of scan issues in email.
 *
 * @package WP_Defender
 */

?>
<table class="reports-list" align="center"
		style="border-collapse: collapse;border-spacing: 0;margin: 0 0 30px;padding: 0;text-align: left;vertical-align: top;width: 100%">
	<thead>
	<tr>
		<th style="padding-left: 20px;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;line-height: 27px; letter-spacing: -0.23px; text-align: left; background-color: #F2F2F2; border-radius: 4px 0 0 0; overflow:hidden;">
			<?php esc_html_e( 'File', 'wpdef' ); ?>
		</th>
		<th style="padding-right: 20px;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;font-weight: 500;line-height: 27px; letter-spacing: -0.23px; text-align: right; width:180px; background-color: #F2F2F2; border-radius: 0 4px 0 0; overflow:hidden;">
			<?php esc_html_e( 'Issue', 'wpdef' ); ?>
		</th>
	</tr>
	</thead>
	<tbody>
	<?php
	$abs_path = untrailingslashit( ABSPATH );
	foreach ( $issues as $k => $item ) {
		$detail    = $item->to_array();
		$full_path = '';
		if ( ! empty( $detail['full_path'] ) ) {
			$full_path = esc_html( $detail['full_path'] );
			if ( 0 === strpos( $full_path, $abs_path ) ) {
				$full_path = $abs_path . '<span>' . substr( $full_path, strlen( $abs_path ) ) . '</span>';
			}
		}
		?>
		<tr class="report-list-item" style="border: 1px solid #F2F2F2;padding: 0;text-align: left;vertical-align: top">
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;line-height: 22px;font-weight: 500;letter-spacing: -0.23px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
				<span style="color: inherit;display: inline-block;font-size: inherit;font-weight: inherit;font-family: inherit;line-height: inherit;vertical-align: middle;letter-spacing: -0.25px;padding-left: 20px;">
					<?php echo esc_html( $detail['file_name'] ); ?>
					<span class="report-list-item-path"
							style="display: inline-block; width: 100%;"><?php echo wp_kses( $full_path, array( 'span' => array() ) ); ?></span>
				</span>
			</td>
			<td class="report-list-item-info"
				style="border-collapse: collapse !important;color: #1A1A1A;font-family: Roboto, Arial, sans-serif;font-size: 12px;line-height: 22px;font-weight: 500;letter-spacing: -0.25px;margin: 0;padding: 18px 0;text-align: left;vertical-align: top">
				<span style="color: inherit;display: inline-block;font-size: inherit;font-weight: inherit;font-family: inherit;line-height: inherit;vertical-align: middle;letter-spacing: -0.25px;padding-right: 20px;"><?php echo esc_html( $detail['short_desc'] ); ?></span>
			</td>
		</tr>
	<?php } ?>
	</tbody>
</table>
<p style="font-family: Roboto, Arial, sans-serif;font-size: 16px;font-weight: normal;line-height: 10px;margin: 0;padding: 0;text-align: center">
	<a href="
	<?php
	echo esc_url(
		apply_filters(
			'report_email_logs_link',
			network_admin_url( 'admin.php?page=wdf-scan' ),
			$email
		)
	);
	?>
		"
		class="button view-full"
		style="font-family: Roboto, Arial, sans-serif;font-size: 16px;font-weight: normal;line-height: 20px;text-align: center; margin-bottom:0;">
		<?php esc_html_e( 'View Full Report', 'wpdef' ); ?>
	</a>
</p>