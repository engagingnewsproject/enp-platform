<?php
/**
 * Configure tool template for gdpr onboarding
 */
?>
<div class="vendor-exclusion">
	<div class="vendor-table">
		<div class="st-box ">
			<div class="vendor-table-header">
				<div class="vendor-name">
					<h3>
						<?php esc_html_e('Vendor names', 'googleanalytics'); ?>
					</h3>
					<div class="st-input vendor-search">
						<input autocomplete="off" id="vendor-search " placeholder="Search for vendor" type="text">
					</div>
				</div>
				</div>
				<p class="vendor-info">
					<?php esc_html_e('Manage third-party vendors asking for consent across your sites.', 'sharethis-share-buttons'); ?>
				</p>
			</div>
			<div class="vendor-table-body">
				<?php foreach ($vendors as $vendor ) : ?>
				<div class="vendor-table-cell">
					<a name="<?php echo strtolower(esc_html($vendor['name'])); ?>"></a>
					<a name="<?php echo strtolower(esc_html(explode(' ', $vendor['name'])[0])); ?>"></a>
					<div class="vendor-table-cell-wrapper switch">
						<label>
							<input data-id="<?php echo esc_attr($vendor['id']); ?>" type="checkbox" name="vendor[<?php echo esc_attr($vendor['id']); ?>]" value="consent" />
							<span class="lever"></span>
							<strong><?php echo esc_html($vendor['name']); ?></strong>
						</label>
						<div class="vendor-accor">
							<p>
								<strong><?php esc_html_e('Privacy Policy: ', 'sharethis-share-buttons'); ?></strong>
								           <?php echo '<a href="' . $vendor['policyUrl'] . '" target="_blank">' .
								           $vendor['policyUrl'] . '</a>'; ?>
							</p>
							<p>
								<strong><?php esc_html_e('Purposes: ', 'sharethis-share-buttons'); ?></strong>
								<div class="vendor-purpose-list">
									<?php foreach ($vendor['purposes'] as $purpose) : ?>
							<p><?php echo esc_html($purposes[$purpose]); ?></p>
							<?php endforeach; ?>
						</div>
						</p>
					</div>
				</div>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
</div>
