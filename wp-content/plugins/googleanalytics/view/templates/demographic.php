<?php
$enabledisable = get_option('googleanalytics_demographic') === '1' ? 'Disable' : 'Enable';
?>
<?php if ('Enable' === $enabledisable) : ?>
	<tr valign="top">
		<th scope="row"><?php esc_html_e('Enable demographic charts', 'googleanalytics'); ?>:</th>
	</tr>
	<?php if ( Ga_Helper::are_features_enabled() ) : ?>
		<td>
			<button id="demographic-popup"><?php esc_html_e('Enable', 'googleanalytics'); ?></button>
		</td>
	<?php else : ?>
		<td>
			<label class="<?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'label-grey ga-tooltip' : '' ?>">
				<button class="gdpr-enable" disabled="disabled"><?php esc_html_e('Enable'); ?></button>
				<span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
			</label>
		</td>
	<?php endif; ?>
<?php endif; ?>
