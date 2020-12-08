<div class="col-md-12">
	<h3><?php echo esc_html__( 'Form Color', 'googleanalytics' ); ?></h3>
</div>

<div id="sharethis-form-color" class="col-md-12">
	<?php foreach ($colors as $color) : ?>
		<div class="color<?php echo isset($gdpr_config['color']) && $color === $gdpr_config['color'] ? ' selected' : ''; ?>"
		     data-value="<?php echo esc_attr($color); ?>"
		     style="max-width: 30px; max-height: 30px; overflow: hidden;"
		>
			<span style="content: ' '; background-color:<?php echo esc_html($color); ?>; padding: 40px;"></span>
		</div>
	<?php endforeach; ?>
</div>
