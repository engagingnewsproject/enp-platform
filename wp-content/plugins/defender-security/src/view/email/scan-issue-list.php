<table class="results-list"
	style="border-collapse: collapse; border-spacing: 0; padding: 0; text-align: left; vertical-align: top;">
	<thead class="results-list-header"
		style="border-bottom: 2px solid #ff5c28;">
	<tr style="padding: 0; text-align: left; vertical-align: top;">
		<th class="result-list-label-title"
			style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left; width: 35%;"><?php esc_html_e( "File",'wpdef' ); ?></th>
		<th class="result-list-data-title"
			style="Margin: 0; color: #ff5c28; font-family: Helvetica, Arial, sans-serif; font-size: 22px; font-weight: 700; line-height: 48px; margin: 0; padding: 0; text-align: left;"><?php esc_html_e( "Issue", 'wpdef' ); ?></th>
	</tr>
	</thead>
	<tbody class="results-list-content">
	<?php
		foreach ( $issues as $k => $item ) {
			$detail = $item->to_array();
			if ( $k == 0 ) {
			?>
			<tr style="padding: 0; text-align: left; vertical-align: top;">
				<td class="result-list-label"
					style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;">
					<?php echo $detail['file_name']; ?>
					<span
						style="display: inline-block; font-weight: 400; width: 100%;"><?php echo isset( $detail['full_path'] ) ? $detail['full_path'] : null; ?></span>
				</td>
				<td class="result-list-data"
					style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;">
					<?php echo $detail['short_desc']; ?></td>
			</tr>
		<?php } else { ?>
			<tr style="padding: 0; text-align: left; vertical-align: top;">
				<td class="result-list-label <?php echo $k > 0 ? " bordered" : null; ?>"
					style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; border-top: 2px solid #ff5c28; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;">
					<?php echo $detail['file_name']; ?>
					<span
						style="display: inline-block; font-weight: 400; width: 100%;"><?php echo isset( $detail['full_path'] ) ? $detail['full_path'] : null; ?></span>
				</td>
				<td class="result-list-data <?php echo $k > 0 ? " bordered" : null; ?>"
					style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; border-top: 2px solid #ff5c28; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: 700; hyphens: auto; line-height: 28px; margin: 0; padding: 20px 5px; text-align: left; vertical-align: top; word-wrap: break-word;">
					<?php echo $detail['short_desc']; ?></td>
			</tr>
		<?php } ?>
	<?php } ?>
	</tbody>
	<tfoot class="results-list-footer">
		<tr style="padding: 0; text-align: left; vertical-align: top;">
			<td colspan="2"
				style="-moz-hyphens: auto; -webkit-hyphens: auto; Margin: 0; border-collapse: collapse !important; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; hyphens: auto; line-height: 26px; margin: 0; padding: 10px 0 0; text-align: left; vertical-align: top; word-wrap: break-word;">
				<p style="Margin: 0; Margin-bottom: 0; color: #555555; font-family: Helvetica, Arial, sans-serif; font-size: 15px; font-weight: normal; line-height: 26px; margin: 0; margin-bottom: 0; padding: 0 0 24px; text-align: left;">
					<a class="plugin-brand"
						href="<?php echo network_admin_url( 'admin.php?page=wdf-scan' ); ?>"
						style="Margin: 0; color: #ff5c28; display: inline-block; font: inherit; font-family: Helvetica, Arial, sans-serif; font-weight: normal; line-height: 1.3; margin: 0; padding: 0; text-align: left; text-decoration: none;"><?php esc_html_e( "Let's get your site patched up.", 'wpdef' ); ?>
						<img class="icon-arrow-right"
							src="<?php echo defender_asset_url( '/assets/email-images/icon-arrow-right-defender.png' ); ?>"
							alt="Arrow"
							style="-ms-interpolation-mode: bicubic; border: none; clear: both; display: inline-block; margin: -2px 0 0 5px; max-width: 100%; outline: none; text-decoration: none; vertical-align: middle; width: auto;">
					</a>
				</p>
			</td>
		</tr>
	</tfoot>
</table>
