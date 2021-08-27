<?php
/**
 * General meta box.
 *
 * @since 2.2.0
 * @package Hummingbird
 *
 * @var bool|array $cache_control     Cache control setting in admin bar.
 * @var array      $caching_modules   List of active caching modules.
 * @var string     $site_language     Site language.
 * @var string     $translation_link  Link to translations.
 * @var bool       $tracking          Tracking status.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p><?php esc_html_e( 'Configure general settings for this plugin.', 'wphb' ); ?></p>

<form method="post" class="settings-frm">
	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Admin Bar', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( 'Add a shortcut to Hummingbird settings in the top WordPress Admin bar.', 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="control" class="sui-toggle">
					<input type="hidden" name="control" value="0" />
					<input type="checkbox" id="control" name="control" aria-labelledby="control-label" <?php checked( (bool) $cache_control ); ?> />
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="control-label" class="sui-toggle-label">
						<?php esc_html_e( 'Show Clear Cache button in Admin area', 'wphb' ); ?>
					</span>
				</label>

				<div class="sui-toggle-content cache-control-options <?php echo $cache_control ? '' : 'sui-hidden'; ?>">
					<div class="sui-description">
						<?php esc_html_e( 'Choose the types of cache to be cleared when the Clear Cache button is clicked in the WordPress Admin Bar.', 'wphb' ); ?>
					</div>

					<div class="sui-side-tabs sui-tabs" style="margin-top: 10px">
						<div data-tabs>
							<label for="all_cache" class="sui-tab-item<?php echo checked( ! is_array( $cache_control ) ) ? ' active' : ''; ?>">
								<input type="radio" name="type" value="all" id="all_cache" <?php checked( ! is_array( $cache_control ) ); ?>>
								<?php esc_html_e( 'All Cache', 'wphb' ); ?>
							</label>
							<label for="custom_cache" class="sui-tab-item<?php echo checked( is_array( $cache_control ) ) ? ' active' : ''; ?>">
								<input type="radio" name="type" value="custom" id="custom_cache" <?php checked( is_array( $cache_control ) ); ?>>
								<?php esc_html_e( 'Specific Cache', 'wphb' ); ?>
							</label>
						</div>

						<div data-panes>
							<div class="sui-tab-boxed <?php echo is_array( $cache_control ) ? '' : 'active'; ?>">
								<p class="sui-description">
									<?php
									printf( /* translators: %1$s - <strong>, %2$s - </strong> */
										esc_html__( 'When this option is selected, clicking the Clear Cache button in the WordPress Admin Bar will clear all active cache types, such as %1$sPage Cache, Asset Optimization Cache, Redis Cache%2$s, and %1$sCloudflare Cache%2$s.', 'wphb' ),
										'<strong>',
										'</strong>'
									)
									?>
								</p>
							</div>
							<div class="sui-tab-boxed <?php echo is_array( $cache_control ) ? 'active' : ''; ?>">
								<p class="sui-description">
									<?php esc_html_e( 'When this option is selected, a dropdown menu will appear in the WordPress Admin Bar. Select the cache types you wish to be able to clear individually from that dropdown menu. Only active cache types are shown here.', 'wphb' ); ?>
								</p>

								<div class="sui-form-field">
									<?php foreach ( $caching_modules as $module_id => $module_name ) : ?>
										<?php $checked = is_array( $cache_control ) && in_array( $module_id, $cache_control, true ); ?>
										<label for="<?php echo esc_attr( $module_id ); ?>" class="sui-checkbox sui-checkbox-stacked">
											<input type="checkbox" name="<?php echo esc_attr( $module_id ); ?>" id="<?php echo esc_attr( $module_id ); ?>" <?php checked( $checked ); ?>>
											<span aria-hidden="true"></span>
											<span class="sui-description sui-description-sm">
												<?php echo esc_html( $module_name ); ?>
											</span>
										</label>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label "><?php esc_html_e( 'Translations', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php
				printf(
					/* translators: %1$s: opening a tag, %2$s: closing a tag */
					esc_html__( 'By default, Hummingbird will use the language you’d set in your %1$sWordPress Admin Settings%2$s if a matching translation is available.', 'wphb' ),
					'<a href="' . esc_html( admin_url( 'options-general.php' ) ) . '">',
					'</a>'
				);
				?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="language-input" class="sui-label">
					<?php esc_html_e( 'Active Translation', 'wphb' ); ?>
				</label>
				<input type="text" id="language-input" class="sui-form-control" disabled="disabled" placeholder="<?php echo esc_attr( $site_language ); ?>">
				<span class="sui-description">
					<?php
					printf(
						/* translators: %1$s: opening a tag, %2$s: closing a tag */
						esc_html__( 'Not using your language, or have improvements? Help us improve translations by providing your own improvements %1$shere%2$s.', 'wphb' ),
						'<a href="' . esc_html( $translation_link ) . '" target="_blank">',
						'</a>'
					);
					?>
				</span>
			</div>
		</div>
	</div>

	<div class="sui-box-settings-row">
		<div class="sui-box-settings-col-1">
			<span class="sui-settings-label"><?php esc_html_e( 'Usage Tracking', 'wphb' ); ?></span>
			<span class="sui-description">
				<?php esc_html_e( "Help make Hummingbird better by letting our designers learn how you're using the plugin.", 'wphb' ); ?>
			</span>
		</div>
		<div class="sui-box-settings-col-2">
			<div class="sui-form-field">
				<label for="tracking" class="sui-toggle">
					<input type="hidden" name="tracking" value="0" />
					<input
						type="checkbox"
						id="tracking"
						name="tracking"
						aria-labelledby="tracking-label"
						aria-describedby="tracking-description"
						<?php checked( $tracking ); ?>
					/>
					<span class="sui-toggle-slider" aria-hidden="true"></span>
					<span id="tracking-label" class="sui-toggle-label">
						<?php esc_html_e( 'Allow usage tracking', 'wphb' ); ?>
					</span>
					<span id="tracking-description" class="sui-description">
						<?php
						printf(
							__( "Note: Usage tracking is completely anonymous. We are only tracking what features you are/aren't using to make our feature decision more informed. You can read about what data will be collected <a href='%s' target='_blank'>here</a>.", 'wphb' ),
							'https://wpmudev.com/docs/privacy/our-plugins/#usage-tracking'
						);
						?>
					</span>
				</label>
			</div>
		</div>
	</div>
</form>
