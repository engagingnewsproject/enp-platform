<?php
/**
 * Dashboard template: Whitelabel Functions
 *
 * @var array                           $projects            Project list.
 * @var array                           $sites               Selected sites.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls                URL class.
 * @var array                           $whitelabel_settings Whitelabel settings.
 * @var string                          $membership_type     Membership type.
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

// Render the page header section.
$this->render_sui_header(
	__( 'White Label', 'wpmudev' ),
	'whitelabel'
);
$url_upgrade_to_agency = sprintf( '%s%s', $urls->remote_site, '/hub/account/' );
$url_upgrade_expired   = sprintf( '%s%s', $urls->remote_site, '/hub/account/?utm_source=wpmudev-dashboard&utm_medium=plugin&utm_campaign=dashboard_expired_modal_reactivate' );
$can_use_whitelabel    = WPMUDEV_Dashboard::$api->is_whitelabel_allowed();
?>

<?php if ( isset( $_GET['success-action'] ) ) : // phpcs:ignore ?>
	<div class="sui-floating-notices">

		<?php
		switch ( $_GET['success-action'] ) { // phpcs:ignore
			case 'whitelabel-setup':
				$notice_msg = '<p>' . esc_html__( 'White Label configuration has been saved.', 'wpmudev' ) . '</p>';
				$notice_id  = 'whitelabel-success';
				break;
			case 'check-updates':
				$notice_msg = '<p>' . esc_html__( 'Data successfully updated.', 'wpmudev' ) . '</p>';
				$notice_id  = 'remote-check-success';
			default:
				break;
		}
		?>
		<div
			role="alert"
			id="<?php echo esc_attr( $notice_id ); ?>"
			class="sui-tools-notice-alert sui-notice"
			aria-live="assertive"
			data-show-dismiss="true"
			data-notice-type="success"
			data-notice-msg="<?php echo wp_kses_post( $notice_msg ); ?>"
		>
		</div>
	</div>
<?php endif; ?>

	<div class="sui-row-with-sidenav">
		<div class="sui-box" id="whitelabel">
			<div class="sui-box-header">
				<h2 class="sui-box-title"><?php esc_html_e( 'White Label', 'wpmudev' ); ?></h2>
			</div>
			<form method="POST" action="<?php echo esc_url( $urls->whitelabel_url ); ?>" id="wpmudev-whitelabel-settings-form">
				<input type="hidden" name="action" value="whitelabel-setup"/>
				<?php wp_nonce_field( 'whitelabel-setup', 'hash' ); ?>
				<?php if ( $whitelabel_settings['enabled'] && $can_use_whitelabel ) : ?>
					<div class="sui-box-body">
						<p><?php esc_html_e( 'Remove WPMU DEV branding from all our plugins and replace it with your own branding for your clients.', 'wpmudev' ); ?></p>
						<?php
						// SETTING: WPMU DEV Branding.
						?>
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label"><?php esc_html_e( 'WPMU DEV branding', 'wpmudev' ); ?></span>
								<span class="sui-description"><?php esc_html_e( 'Remove Super Hero images from our plugins entirely, and upload or link your own logo for the dashboard section of each plugin.', 'wpmudev' ); ?></span>
							</div>

							<div class="sui-box-settings-col-2">
								<div
									class="sui-side-tabs"
									data-checkbox="branding_enabled"
								>
									<div class="sui-tabs-menu">
										<label
											for="wpmudev-branding-default"
											class="sui-tab-item <?php echo 'default' === $whitelabel_settings['branding_type'] ? 'active' : ''; ?>"
										>
											<input
												type="radio"
												name="branding_type"
												value="default"
												id="wpmudev-branding-default"
												data-checked="<?php echo 'default' === $whitelabel_settings['branding_type'] ? 'true' : 'false'; ?>"
												<?php checked( $whitelabel_settings['branding_type'], 'default' ); ?>
											/>
											<?php esc_html_e( 'Default', 'wpmudev' ); ?>
										</label>
										<label
											for="wpmudev-branding-custom"
											class="sui-tab-item <?php echo 'custom' === $whitelabel_settings['branding_type'] ? 'active' : ''; ?>"
										>
											<input
												type="radio"
												name="branding_type"
												value="custom"
												id="wpmudev-branding-custom"
												data-checked="<?php echo 'custom' === $whitelabel_settings['branding_type'] ? 'true' : 'false'; ?>"
												data-tab-menu="wpmudev-branding-upload"
												<?php checked( $whitelabel_settings['branding_type'], 'custom' ); ?>
											/>
											<?php esc_html_e( 'Upload logo', 'wpmudev' ); ?>
										</label>
										<label
											for="wpmudev-branding-link"
											class="sui-tab-item <?php echo 'link' === $whitelabel_settings['branding_type'] ? 'active' : ''; ?>"
										>
											<input
												type="radio"
												name="branding_type"
												value="link"
												id="wpmudev-branding-link"
												data-checked="<?php echo 'link' === $whitelabel_settings['branding_type'] ? 'true' : 'false'; ?>"
												data-tab-menu="wpmudev-branding-link-logo"
												<?php checked( $whitelabel_settings['branding_type'], 'link' ); ?>
											/>
											<?php esc_html_e( 'Link logo', 'wpmudev' ); ?>
										</label>
									</div>
									<div class="sui-tabs-content">
										<div
											id="wpmudev-branding-upload"
											class="sui-tab-content sui-tab-boxed <?php echo 'custom' === $whitelabel_settings['branding_type'] ? 'active' : ''; ?>"
											data-tab-content="wpmudev-branding-upload"
										>
											<div class="sui-form-field">
												<label class="sui-label">
													<?php esc_html_e( 'Upload Logo (optional)', 'wpmudev' ); ?>
												</label>
												<div
													id="branding_upload"
													class="sui-upload <?php echo esc_attr( $whitelabel_settings['branding_image'] ? 'sui-has_file' : '' ); ?>"
												>
													<div class="sui-hidden">
														<input
															type="text"
															name="branding_image"
															id="branding_image"
															readonly="readonly"
															value="<?php echo esc_attr( $whitelabel_settings['branding_image'] ); ?>"
														>
													</div>
													<input
														type="hidden"
														name="branding_image_id"
														id="branding_image_id"
														readonly="readonly"
														value="<?php echo esc_attr( $whitelabel_settings['branding_image_id'] ); ?>"
													>
													<div class="sui-upload-image" aria-hidden="true">
														<div class="sui-image-mask"></div>
														<div
															role="button"
															class="sui-image-preview wp-browse-media"
															data-frame-title="<?php esc_html_e( 'Select or Upload Media for Branding Logo', 'wpmudev' ); ?>"
															data-button-text="<?php esc_html_e( 'Use this as Branding Logo', 'wpmudev' ); ?>"
															data-input-id="branding_image"
															data-preview-id="branding_image_preview"
															data-upload-wrapper-id="branding_upload"
															data-input-id-container="branding_image_id"
															data-text-id="branding_image_text"
															id="branding_image_preview"
															style="background-image: url('<?php echo esc_url( $whitelabel_settings['branding_image'] ); ?>');"
														>
														</div>
													</div>
													<button
														class="sui-upload-button wp-browse-media"
														data-frame-title="<?php esc_html_e( 'Select or Upload Media for Branding Logo', 'wpmudev' ); ?>"
														data-button-text="<?php esc_html_e( 'Use this as Branding Logo', 'wpmudev' ); ?>"
														data-input-id="branding_image"
														data-preview-id="branding_image_preview"
														data-upload-wrapper-id="branding_upload"
														data-text-id="branding_image_text"
													>
														<i class="sui-icon-upload-cloud" aria-hidden="true"></i> <?php esc_html_e( 'Upload image', 'wpmudev' ); ?>
													</button>
													<div class="sui-upload-file">
														<span id="branding_image_text"><?php echo esc_url( $whitelabel_settings['branding_image'] ); ?></span>
														<button
															class="js-clear-image"
															aria-label="<?php esc_attr_e( 'Remove', 'wpmudev' ); ?>"
															data-media-button-id="branding_image_preview"
														>
															<i class="sui-icon-close" aria-hidden="true"></i>
														</button>
													</div>
												</div>
												<span class="sui-description"><?php esc_html_e( 'Maximum height and width of logo should be 192px and 172px respectively. This Logo will appear only in the dashboard section of each WPMU DEV plugin you have installed that supports this feature.', 'wpmudev' ); ?></span>
											</div>
											<?php if ( is_multisite() ) : ?>
												<div class="sui-form-field">
													<label class="sui-toggle">
														<input
															type="checkbox"
															name="branding_enabled_subsite"
															value="1"
															id="branding_enabled_subsite"
															<?php checked( $whitelabel_settings['branding_enabled_subsite'] ); ?>
														/>
														<span class="sui-toggle-slider"></span>
													</label>
													<label for="branding_enabled_subsite" class="sui-toggle-label"><?php esc_html_e( 'Allow Subsite Admins to override', 'wpmudev' ); ?></label>
													<span class="sui-description"><?php esc_html_e( 'By default, subsites will inherit the main branding set here. With this setting enabled, we will use the logo set in the Customizer Menu as the branding across plugins.', 'wpmudev' ); ?></span>
												</div>
											<?php endif; ?>
										</div>
										<div
											id="wpmudev-branding-link-logo"
											class="sui-tab-content sui-tab-boxed wpmudev-whitelabel-link-logo <?php echo 'link' === $whitelabel_settings['branding_type'] ? 'active' : ''; ?>"
											data-tab-content="wpmudev-branding-link-logo"
										>
											<div class="sui-form-field" id="branding_link_form_field">
												<label for="branding_image_link" id="branding_image_link_label" class="sui-label">
													<?php esc_html_e( 'Insert Logo from URL', 'wpmudev' ); ?>
												</label>
												<div class="sui-upload">
													<div class="sui-upload-image" aria-hidden="true">
														<div class="sui-image-mask"></div>
														<div
															class="sui-image-link-preview <?php echo '' === esc_url( $whitelabel_settings['branding_image_link'] ) ? '' : 'has-logo-image'; ?>"
															id="branding_link_preview"
															style="background-image: url('<?php echo esc_url( $whitelabel_settings['branding_image_link'] ); ?>');"
														></div>
													</div>
													<div class="sui-with-button sui-with-button-icon">
														<input
															id="branding_image_link"
															name="branding_image_link"
															class="sui-form-control wp-link-media"
															data-preview-id="branding_link_preview"
															data-clear-btn-id="branding_link_clear"
															data-form-field-id="branding_link_form_field"
															data-tab-type-name="branding_type"
															aria-labelledby="branding_image_link_label"
															aria-describedby="branding_image_link_desc"
															value="<?php echo esc_url( $whitelabel_settings['branding_image_link'] ); ?>"
														/>
														<button
															type="button"
															class="sui-button-icon js-clear-link <?php echo empty( $whitelabel_settings['branding_image_link'] ) ? 'hidden-clear-link' : ''; ?>"
															id="branding_link_clear"
														>
															<span aria-hidden="true" class="sui-icon-close"></span>
															<span class="sui-screen-reader-text">
															<?php esc_html_e( 'Remove file', 'wpmudev' ); ?>
														</span>
														</button>
													</div>
												</div>
												<span id="branding_image_link_error" class="sui-error-message" role="alert">
												<?php esc_attr_e( 'Invalid image URL. Please, enter a valid one.', 'wpmudev' ); ?>
											</span>
												<span id="branding_image_link_desc" class="sui-description">
												<?php esc_html_e( 'Maximum height and width of logo should be 192px and 172px respectively. This Logo will appear only in the dashboard section of each WPMU DEV plugin you have installed that supports this feature.', 'wpmudev' ); ?>
											</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						// SETTING: Footer Text.
						?>
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label"><?php esc_html_e( 'Footer Text', 'wpmudev' ); ?></span>
								<span class="sui-description"><?php esc_html_e( 'Remove or replace the default WPMU DEV footer text from all plugin screens.', 'wpmudev' ); ?></span>
							</div>
							<div class="sui-box-settings-col-2">
								<div class="sui-side-tabs">
									<div class="sui-tabs-menu">
										<label
											for="wpmudev-footer-default"
											class="sui-tab-item<?php echo esc_attr( $whitelabel_settings['footer_enabled'] ? '' : ' active' ); ?>"
										>
											<input
												type="radio"
												name="footer_enabled"
												value="0"
												id="wpmudev-footer-default"
												data-checked="false"
											/>
											<?php esc_html_e( 'Default', 'wpmudev' ); ?>
										</label>
										<label
											for="wpmudev-footer-custom"
											class="sui-tab-item<?php echo esc_attr( $whitelabel_settings['footer_enabled'] ? ' active' : '' ); ?>"
										>
											<input
												type="radio"
												name="footer_enabled"
												value="1"
												id="wpmudev-footer-custom"
												data-checked="true"
												data-tab-menu="wpmudev-footer-upload"
												<?php checked( $whitelabel_settings['footer_enabled'] ); ?> />
											<?php esc_html_e( 'Custom', 'wpmudev' ); ?>
										</label>
									</div>
									<div class="sui-tabs-content">
										<div
											id="wpmudev-branding-footer"
											class="sui-tab-content sui-tab-boxed<?php echo esc_attr( $whitelabel_settings['footer_enabled'] ? ' active' : '' ); ?>"
											data-tab-content="wpmudev-footer-upload"
										>
											<div class="sui-form-field">
												<label class="sui-label" for="footer_text"><?php esc_html_e( 'Footer text', 'wpmudev' ); ?></label>
												<input
													type="text"
													name="footer_text"
													value="<?php echo esc_attr( $whitelabel_settings['footer_text'] ); ?>"
													placeholder="<?php esc_html_e( 'Your brand name', 'wpmudev' ); ?>"
													id="footer_text"
													class="sui-form-control"
												/>
												<span class="sui-description"><?php esc_html_e( 'Leave the field empty to hide the footer completely.', 'wpmudev' ); ?></span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						// SETTING: Admin Menu Labels.
						?>
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label"><?php esc_html_e( 'WPMU DEV Plugin Labels', 'wpmudev' ); ?></span>
								<span class="sui-description"><?php esc_html_e( 'Adjust the WPMU DEV plugin labels in WordPress Admin menu to suit your white label needs.', 'wpmudev' ); ?></span>
								<span class="sui-description">
									<?php
									$branda     = WPMUDEV_Dashboard::$site->get_project_info( '9135' );
									$branda_url = $branda->is_active ? $branda->url->config : $urls->plugins_url . '#pid=9135';
									?>
									<?php
									printf(
									// translators: %s Link to Branda.
										__( 'For more advanced configurations, use <a href="%s">Branda Pro</a>, which allows you to fully white label and rebrand the WordPress Admin interface. Use the Admin Menu module to rename and adjust your sidebar links.', 'wpmudev' ), // phpcs:ignore
										esc_url( $branda_url )
									);
									?>
								</span>
							</div>
							<div class="sui-box-settings-col-2">
								<?php if ( is_network_admin() ) : ?>
									<div class="sui-form-field">
										<h4 class="sui-settings-label"><?php esc_html_e( 'Apply on', 'wpmudev' ); ?></h4>
										<span class="sui-description sui-spacing-bottom--10">
											<?php esc_html_e( 'Choose which subsites on your network you want to inherit the configurations made below. The configurations will only be applied to sites that have the plugins active.', 'wpmudev' ); ?>
										</span>
										<div class="sui-side-tabs">
											<div class="sui-tabs-menu">
												<label
													for="wpmudev-labels-networkwide"
													class="sui-tab-item <?php echo esc_attr( $whitelabel_settings['labels_networkwide'] ? 'active' : '' ); ?>"
												>
													<input
														type="radio"
														name="labels_networkwide"
														value="1"
														id="wpmudev-labels-networkwide"
														data-checked="<?php echo $whitelabel_settings['labels_networkwide'] ? 'true' : 'false'; ?>"
														<?php checked( $whitelabel_settings['labels_networkwide'] ); ?>
													/>
													<?php esc_html_e( 'All Sites', 'wpmudev' ); ?>
												</label>
												<label
													for="wpmudev-labels-subsites"
													class="sui-tab-item <?php echo esc_attr( $whitelabel_settings['labels_networkwide'] ? '' : 'active' ); ?>"
												>
													<input
														type="radio"
														name="labels_networkwide"
														value="0"
														id="wpmudev-labels-subsites"
														data-checked="<?php echo $whitelabel_settings['labels_networkwide'] ? 'false' : 'true'; ?>"
														data-tab-menu="wpmudev-labels-subsites-config"
														<?php checked( $whitelabel_settings['labels_networkwide'], false ); ?> />
													<?php esc_html_e( 'Selected Subsites', 'wpmudev' ); ?>
												</label>
											</div>
											<div class="sui-tabs-content">
												<div
													id="wpmudev-labels-subsites-content"
													class="sui-tab-content sui-tab-boxed <?php echo esc_attr( $whitelabel_settings['labels_networkwide'] ? '' : 'active' ); ?>"
													data-tab-content="wpmudev-labels-subsites-config"
												>
													<?php
													$this->render(
														'sui/whitelabel-settings/sites',
														array(
															'urls'     => $urls,
															'settings' => $whitelabel_settings,
														)
													);
													?>
												</div>
											</div>
										</div>
									</div>
								<?php endif; ?>
								<div class="sui-form-field">
									<?php if ( is_network_admin() ) : ?>
										<h4 class="sui-settings-label"><?php esc_html_e( 'Plugin Label Configurations', 'wpmudev' ); ?></h4>
										<span class="sui-description sui-spacing-bottom--10">
											<?php esc_html_e( 'Configure the Admin menu label for each of the WPMU DEV plugins.', 'wpmudev' ); ?>
										</span>
									<?php endif; ?>
									<div class="sui-side-tabs">
										<div class="sui-tabs-menu">
											<label
												for="wpmudev-labels-default"
												class="sui-tab-item <?php echo esc_attr( $whitelabel_settings['labels_enabled'] ? '' : 'active' ); ?>"
											>
												<input
													type="radio"
													name="labels_enabled"
													value="0"
													id="wpmudev-labels-default"
													data-checked="<?php echo $whitelabel_settings['labels_enabled'] ? 'false' : 'true'; ?>"
													<?php checked( $whitelabel_settings['labels_enabled'], false ); ?>
												/>
												<?php esc_html_e( 'Default', 'wpmudev' ); ?>
											</label>
											<label
												for="wpmudev-labels-custom"
												class="sui-tab-item <?php echo esc_attr( $whitelabel_settings['labels_enabled'] ? 'active' : '' ); ?>"
											>
												<input
													type="radio"
													name="labels_enabled"
													value="1"
													id="wpmudev-labels-custom"
													data-checked="<?php echo $whitelabel_settings['labels_enabled'] ? 'true' : 'false'; ?>"
													data-tab-menu="wpmudev-labels-config"
													<?php checked( $whitelabel_settings['labels_enabled'] ); ?> />
												<?php esc_html_e( 'Custom', 'wpmudev' ); ?>
											</label>
										</div>
										<div class="sui-tabs-content">
											<div
												id="wpmudev-labels-custom-content"
												class="sui-tab-content <?php echo esc_attr( $whitelabel_settings['labels_enabled'] ? 'active' : '' ); ?>"
												data-tab-content="wpmudev-labels-config"
											>
												<?php
												$plugin_projects = array();
												foreach ( $projects as $project ) {
													// Get project data.
													$project = WPMUDEV_Dashboard::$site->get_project_info( $project['id'] );
													// Include only active items.
													if ( ! empty( $project ) && $project->is_installed && 119 !== $project->pid ) {
														// On multisite, show all installed items.
														if ( is_multisite() || $project->is_active ) {
															$plugin_projects[ $project->pid ] = $project;
														}
													}
												}
												?>
												<?php if ( ! empty( $plugin_projects ) ) : ?>
													<div class="sui-tabs sui-tabs-overflow">
														<div
															tabindex="-1"
															class="sui-tabs-navigation"
															aria-hidden="true"
														>
															<button
																type="button"
																class="sui-button-icon sui-tabs-navigation--left"
															>
																<span class="sui-icon-chevron-left"></span>
															</button>
															<button
																type="button"
																class="sui-button-icon sui-tabs-navigation--right"
															>
																<span class="sui-icon-chevron-right"></span>
															</button>
														</div>
														<div role="tablist" class="sui-tabs-menu">
															<input
																type="hidden"
																name="labels_config_selected"
																id="wpmudev-labels-config-selected"
																value="<?php echo esc_attr( $whitelabel_settings['labels_config_selected'] ); ?>"
															>
															<?php $i = 0; ?>
															<?php foreach ( $plugin_projects as $pid => $project ) : ?>
																<?php
																if ( ! empty( $whitelabel_settings['labels_config_selected'] ) ) {
																	$selected = (int) $whitelabel_settings['labels_config_selected'] === (int) $pid;
																} else {
																	$selected = 0 === $i;
																}
																?>
																<button
																	type="button"
																	role="tab"
																	id="tab-wpmudev-plugin-<?php echo (int) $pid; ?>"
																	data-pid="<?php echo (int) $pid; ?>"
																	class="sui-tab-item tab-content-wpmudev-plugin-item <?php echo $selected ? 'active' : ''; ?>"
																	aria-controls="tab-content-wpmudev-plugin-<?php echo (int) $pid; ?>"
																	aria-selected="<?php echo $selected ? 'true' : 'false'; ?>"
																>
																	<?php echo esc_attr( $project->name ); ?>
																</button>
																<?php $i ++; ?>
															<?php endforeach; ?>
														</div>
														<div class="sui-tabs-content">
															<?php $i = 0; ?>
															<?php foreach ( $plugin_projects as $pid => $project ) : ?>
																<?php
																if ( ! empty( $whitelabel_settings['labels_config_selected'] ) ) {
																	$selected = (int) $whitelabel_settings['labels_config_selected'] === (int) $pid;
																} else {
																	$selected = 0 === $i;
																}
																?>
																<div
																	role="tabpanel"
																	tabindex="0"
																	id="tab-content-wpmudev-plugin-<?php echo (int) $pid; ?>"
																	class="sui-tab-content <?php echo $selected ? 'active' : ''; ?>"
																	aria-labelledby="tab-wpmudev-plugin-<?php echo (int) $pid; ?>"
																>
																	<?php
																	$this->render(
																		'sui/whitelabel-settings/settings',
																		array(
																			'project'  => $project,
																			'settings' => $whitelabel_settings,
																		)
																	);
																	?>
																</div>
																<?php $i ++; ?>
															<?php endforeach; ?>
														</div>
													</div>
												<?php else : ?>
													<div
														id="wpmudev-labels-custom-no-plugins"
														class="sui-notice"
													>
														<div class="sui-notice-content">
															<div class="sui-notice-message">
																<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
																<p>
																	<?php
																	printf(
																	// translators: %s Plugins page link.
																		__( 'You have no active WPMU DEV plugins. Use the <a href="%s">Plugins tab</a> to install and activate plugins, and then customize the plugin labels here.', 'wpmudev' ), // phpcs:ignore
																		esc_url( $urls->plugins_url )
																	);
																	?>
																</p>
															</div>
														</div>
													</div>
												<?php endif; ?>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php // SETTING: Documentation Links. ?>
						<div class="sui-box-settings-row">
							<div class="sui-box-settings-col-1">
								<span class="sui-settings-label"><?php esc_html_e( 'Documentation, Tutorials and What’s New Modal', 'wpmudev' ); ?></span>
								<span class="sui-description"><?php esc_html_e( 'Remove the documentation button and the WPMU DEV tutorials from all plugin screens. This will also hide the What\'s New feature highlight modal that appears when a plugin is updated.', 'wpmudev' ); ?></span>
							</div>
							<div class="sui-box-settings-col-2">
								<div class="sui-side-tabs">
									<div class="sui-tabs-menu">
										<label
											for="wpmudev-documentation-links-show"
											class="sui-tab-item<?php echo esc_attr( $whitelabel_settings['doc_links_enabled'] ? '' : ' active' ); ?>"
										>
											<input
												type="radio"
												name="doc_links_enabled"
												value="0"
												id="wpmudev-documentation-links-show"
												data-checked="false"
											/>
											<?php esc_html_e( 'Show', 'wpmudev' ); ?>
										</label>
										<label
											for="wpmudev-documentation-links-hide"
											class="sui-tab-item<?php echo esc_attr( $whitelabel_settings['doc_links_enabled'] ? ' active' : '' ); ?>"
										>
											<input
												type="radio"
												name="doc_links_enabled"
												value="1"
												id="wpmudev-documentation-links-hide"
												data-checked="true"
												<?php checked( $whitelabel_settings['doc_links_enabled'] ); ?>
											/>
											<?php esc_html_e( 'Hide', 'wpmudev' ); ?>
										</label>
									</div>
								</div>
							</div>
						</div>
					</div>
				<?php else : ?>
					<div class="sui-message sui-message-lg">
					<?php if ( false === $can_use_whitelabel || in_array( $membership_type, array( 'expired', 'paused' ), true ) ) : ?>
						<img
							src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upgrade.png' ); ?>"
							srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upgrade.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/upgrade@2x.png' ); ?> 2x"
							alt="Upgrade"
							class="sui-image"
							aria-hidden="true"
						/>
					<?php else : ?>
						<img
							src="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?>"
							srcset="<?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module.png' ); ?> 1x, <?php echo esc_url( WPMUDEV_Dashboard::$site->plugin_url . 'assets/images/module@2x.png' ); ?> 2x"
							alt="Whitelabel"
							class="sui-image"
							aria-hidden="true"
						/>
					<?php endif; ?>
						<p><?php esc_html_e( 'Remove WPMU DEV branding from all our plugins and replace it with your own branding for your clients.', 'wpmudev' ); ?></p>
						<?php if ( in_array( $membership_type, array( 'expired', 'paused' ), true ) ) : ?>
							<a href="<?php echo esc_html( $url_upgrade_expired ); ?>" class="sui-button sui-button-purple" style="margin-top: 10px;">
								<?php esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?>
							</a>
						<?php elseif ( false === $can_use_whitelabel ) : ?>
							<a href="<?php echo esc_html( $url_upgrade_to_agency ); ?>" class="sui-button sui-button-purple" style="margin-top: 10px;">
								<?php esc_html_e( 'Upgrade Membership', 'wpmudev' ); ?>
							</a>
						<?php else : ?>
							<button
								type="submit"
								name="status"
								value="activate"
								class="sui-button sui-button-blue"
								<?php disabled( in_array( $membership_type, array( 'expired', 'paused', 'free' ), true ) ); ?>
							>
								<span class="sui-loading-text"><?php esc_html_e( 'Activate', 'wpmudev' ); ?></span>
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
							</button>
						<?php endif; ?>
					</div>
				<?php endif; ?>
				<?php if ( $whitelabel_settings['enabled'] && $can_use_whitelabel ) : ?>
					<div class="sui-box-footer">
						<button
							type="submit"
							name="status"
							value="deactivate"
							class="sui-button sui-button-ghost"
						>
						<span class="sui-loading-text">
							<i class="sui-icon-power-on-off" aria-hidden="true"></i>
							<?php esc_html_e( 'Deactivate', 'wpmudev' ); ?>
						</span>
							<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
						</button>
						<div class="sui-actions-right">
							<button
								type="submit"
								name="status"
								value="settings"
								id="save_changes"
								class="sui-button sui-button-blue"
							>
							<span class="sui-loading-text">
								<i class="sui-icon-save" aria-hidden="true"></i>
								<?php esc_html_e( 'Save Changes', 'wpmudev' ); ?>
							</span>
								<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
							</button>
						</div>
					</div>
				<?php endif; ?>
			</form>
		</div>
	</div>
<?php $this->render_with_sui_wrapper( 'sui/element-last-refresh' ); ?>
<?php
$this->render_with_sui_wrapper( 'sui/footer' );