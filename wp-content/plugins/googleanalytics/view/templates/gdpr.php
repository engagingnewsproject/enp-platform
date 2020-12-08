<?php if(empty($gdpr_config)) : ?>
<tr valign="top">
	<th scope="row"><?php esc_html_e('Enable GDPR Consent Management Tool', 'googleanalytics'); ?>:</th>
</tr>
<tr valign="top">
    <?php if ( Ga_Helper::are_features_enabled() ) : ?>
	<td>
		<button class="gdpr-enable"><?php esc_html_e('Enable'); ?></button>
	</td>
	<?php else : ?>
	<td>
		<label class="<?php echo ( ! Ga_Helper::are_features_enabled() ) ? 'label-grey ga-tooltip' : '' ?>">
		<button class="gdpr-enable" disabled="disabled"><?php esc_html_e('Enable'); ?></button>
		<span class="ga-tooltiptext ga-tt-abs"><?php _e( $tooltip ); ?></span>
		</label>
	</td>
	<?php endif; ?>
</tr>
<?php endif; ?>
