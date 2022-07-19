<?php
/**
 * Dashboard template: Support Functions
 *
 * Manage support tickets, grant support-staff access and view System
 * configuration.
 *
 * Following variables are passed into the template:
 *
 * @var WPMUDEV_Dashboard_Ui            $this              Current instance.
 * @var array                           $member            WPMUDEV_Dashboard::$api->get_profile();
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls              $this->page_urls;
 * @var array                           $allowed_users     WPMUDEV_Dashboard::$site->get_allowed_users();
 * @var string                          $membership_type   WPMUDEV_Dashboard::$api->get_membership_status();
 * @var bool                            $auto_update       WPMUDEV_Dashboard::$settings->get( 'autoupdate_dashboard', 'flags );
 * @var array                           $available_users   Available admin users.
 * @var bool                            $keep_data         Keep settings on uninstall.
 * @var bool                            $preserve_settings Preserve data on uninstall.
 *
 * @since   4.0.0
 * @package WPMUDEV_Dashboard
 */

// Render the page header section.
$page_title = __( 'Settings', 'wpmudev' );
$page_slug  = 'settings';
$this->render_sui_header( $page_title, $page_slug );
$can_manage_users    = true;
$profile             = $member['profile'];
$access_translations = WPMUDEV_Dashboard::$utils->can_access_feature( 'translations' );

// Adding users is only possible when the admin did not define a hardcoded
// user-list in wp-config.
if ( WPMUDEV_LIMIT_TO_USER ) {
	$can_manage_users = false;
}
?>
	<div class="sui-floating-notices">
		<?php
		if ( isset( $_GET['success-action'] ) ) : //phpcs:ignore
			?>
			<?php
			switch ( $_GET['success-action'] ) { //phpcs:ignore
				case 'autoupdate-dashboard':
					$notice_msg = '<p>' . esc_html__( 'General settings updated.', 'wpmudev' ) . '</p>';
					$notice_id  = 'notice-success-update-dashboard';
					break;
				case 'translation-setup':
					$notice_msg = '<p>' . esc_html__( 'Translation settings updated.', 'wpmudev' ) . '</p>';
					$notice_id  = 'notice-success-translation-setup';
					break;
				case 'data-setup':
					$notice_msg = '<p>' . esc_html__( 'Settings updated successfully.', 'wpmudev' ) . '</p>';
					$notice_id  = 'notice-success-data-setup';
					break;
				case 'admin-add':
					$notice_msg = '<p>' . esc_html__( 'User list successfully updated.', 'wpmudev' ) . '</p>';
					$notice_id  = 'notice-success-admin-add';
					break;
				case 'admin-remove':
					$notice_msg = '<p>' . esc_html__( 'User removed.', 'wpmudev' ) . '</p>';
					$notice_id  = 'notice-success-admin-remove';
					break;
				case 'check-updates':
					$notice_msg = '<p>' . esc_html__( 'Data successfully updated.', 'wpmudev' ) . '</p>';
					$notice_id  = 'remote-check-success';
					break;
				case 'reset-settings':
					$notice_msg = '<p>' . esc_html__( 'The plugin settings have been reset.', 'wpmudev' ) . '</p>';
					$notice_id  = 'reset-settings-success';
					break;
				default:
					break;
			}
			?>
			<div
				role="alert"
				id="<?php echo esc_attr( $notice_id ); ?>"
				class="sui-settings-notice-alert sui-notice"
				aria-live="assertive"
				data-show-dismiss="true"
				data-notice-type="success"
				data-notice-msg="<?php echo wp_kses_post( $notice_msg ); ?>"
			>
			</div>
		<?php endif; ?>
		<div
			role="alert"
			id="js-translation-updated"
			class="sui-settings-translation-alert sui-notice"
			aria-live="assertive"
			data-show-dismiss="true"
			data-notice-type="success"
		>
		</div>
	</div>
	<div class="sui-row-with-sidenav">

		<div class="sui-sidenav">

			<ul class="sui-vertical-tabs sui-sidenav-hide-md">

				<li class="sui-vertical-tab">
					<a href="#general">
						<?php esc_html_e( 'General', 'wpmudev' ); ?>
					</a>
				</li>

				<?php if ( $access_translations ) : ?>
					<li class="sui-vertical-tab">
						<a href="#translation" id="tab-translation">
							<?php esc_html_e( 'Translations', 'wpmudev' ); ?>
						</a>
					</li>
				<?php endif; ?>

				<li class="sui-vertical-tab">
					<a href="#permissions">
						<?php esc_html_e( 'Permissions', 'wpmudev' ); ?>
					</a>
				</li>

				<li class="sui-vertical-tab">
					<a href="#apikey"><?php esc_html_e( 'API Key', 'wpmudev' ); ?></a>
				</li>

				<li class="sui-vertical-tab">
					<a href="#data"><?php esc_html_e( 'Data & Settings', 'wpmudev' ); ?></a>
				</li>

			</ul>

			<div class="sui-sidenav-settings">

				<div class="sui-sidenav-hide-lg">

					<select class="sui-select sui-mobile-nav">
						<option value="#general" selected="selected"><?php esc_html_e( 'General', 'wpmudev' ); ?></option>
						<option value="#translation"><?php esc_html_e( 'Translation', 'wpmudev' ); ?></option>
						<option value="#permissions"><?php esc_html_e( 'Permissions', 'wpmudev' ); ?></option>
						<option value="#apikey"><?php esc_html_e( 'API Key', 'wpmudev' ); ?></option>
						<option value="#data"><?php esc_html_e( 'Data & Settings', 'wpmudev' ); ?></option>
					</select>

				</div>

			</div>

		</div>

		<div class="sui-box js-sidenav-content" id="general" style="display: block;">

			<form method="POST" action="<?php echo esc_url( $urls->settings_url ) . '#general'; ?>">

				<input
					type="hidden"
					name="action"
					value="autoupdate-dashboard"
				/>

				<?php wp_nonce_field( 'autoupdate-dashboard', 'hash' ); ?>

				<div class="sui-box-header">
					<h2 class="sui-box-title"><?php esc_html_e( 'General', 'wpmudev' ); ?></h2>
				</div>

				<div class="sui-box-body">

					<div class="sui-box-settings-row">

						<div class="sui-box-settings-col-1">

							<span class="sui-settings-label"><?php esc_html_e( 'Automatic Updates', 'wpmudev' ); ?></span>

							<span class="sui-description"><?php esc_html_e( 'Enable automatic updates of the WPMU DEV Dashboard plugin to ensure API connectivity is always up to date.', 'wpmudev' ); ?></span>

						</div>

						<div class="sui-box-settings-col-2">
							<div class="sui-form-field">
								<label for="autoupdate_dashboard" class="sui-toggle">
									<input
										type="checkbox"
										value="1"
										id="autoupdate_dashboard"
										name="autoupdate_dashboard"
										aria-labelledby="autoupdate_dashboard-label"
										<?php checked( $auto_update ); ?>
									/>
									<span class="sui-toggle-slider" aria-hidden="true"></span>
									<span id="autoupdate_dashboard-label" class="sui-toggle-label">
										<?php esc_html_e( 'Automatically update the WPMU DEV Dashboard plugin', 'wpmudev' ); ?>
									</span>
								</label>
							</div>
						</div>

					</div>

					<div class="sui-box-settings-row">

						<div class="sui-box-settings-col-1">

							<span class="sui-settings-label"><?php esc_html_e( 'Single Sign-on', 'wpmudev' ); ?></span>

							<span class="sui-description"><?php esc_html_e( 'Tired of logging in to your WP Admin area? Enable this setting to be automatically logged in when you visit this site from The Hub.', 'wpmudev' ); ?></span>

						</div>

						<div class="sui-box-settings-col-2">
							<label for="enable_sso" class="sui-toggle">
								<input
									type="checkbox"
									value="1"
									name="enable_sso"
									id="enable_sso"
									aria-labelledby="enable_sso-label"
									<?php checked( $enable_sso ); ?>
								/>
								<span class="sui-toggle-slider" aria-hidden="true"></span>
								<span id="enable_sso-label" class="sui-toggle-label">
									<?php esc_html_e( 'Enable Single Sign-on for this website', 'wpmudev' ); ?>
								</span>
							</label>

							<div class="enable_sso_label">
								<div class="sui-notice">
									<div class="sui-notice-content">
										<div class="sui-notice-message">

											<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>

											<p><?php printf( esc_html__( 'Note: You need to stay logged into %1$1s The Hub%2$2s to use this feature.', 'wpmudev' ), '<a href="https://wpmudev.com/hub2/">', '</a>' ); ?></p>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>

				<div class="sui-box-footer">

					<div class="sui-actions-right">

						<button
							type="submit"
							name="status"
							value="settings"
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

			</form>

		</div>
		<?php if ( $access_translations ) : ?>
			<div class="sui-box js-sidenav-content" id="translation" style="display: none;">

				<form
					method="POST"
					action="<?php echo esc_url( $urls->settings_url ) . '#translation'; ?>"
				>

					<input
						type="hidden"
						name="action"
						value="translation-setup"
					/>

					<?php wp_nonce_field( 'translation-setup', 'hash' ); ?>

					<div class="sui-box-header">
						<h2 class="sui-box-title"><?php esc_html_e( 'Translations', 'wpmudev' ); ?></h2>
					</div>

					<div class="sui-box-body">
						<p>
							<?php esc_html_e( 'Choose the default language, and behaviour for handling translation updates.', 'wpmudev' ); ?>
						</p>
						<div class="sui-box-settings-row">

							<div class="sui-box-settings-col-1">

								<span class="sui-settings-label"><?php esc_html_e( 'Set Translation', 'wpmudev' ); ?></span>

								<span class="sui-description"><?php esc_html_e( 'Choose the default language translation to use on all plugins.', 'wpmudev' ); ?></span>

							</div>

							<div class="sui-box-settings-col-2">
								<span
									class="sui-description"
									style="font-weight: bold; color: #AAA; font-size: 12px; margin-bottom: 5px;"
								>
									<?php esc_html_e( 'Website Language', 'wpmudev' ); ?>
								</span>
								<div id="dashui-dropdown-language">
									<?php
									require_once ABSPATH . 'wp-admin/includes/translation-install.php';
									$languages        = get_available_languages();
									$translations     = wp_get_available_translations();
									$locale           = WPMUDEV_Dashboard::$site->get_option( 'translation_locale' );
									$current_language = get_locale();

									if ( 'en_US' === $current_language ) {
										$current_native_language = __( 'English (United States)', 'wpmudev' );
									} else {
										$current_native_language = isset( $translations[ $current_language ] ) ? $translations[ $current_language ]['native_name'] : $current_language;
									}

									if ( 'en_US' === $locale ) {
										$locale = '';
									}

									wp_dropdown_languages(
										array(
											'name'                        => 'selected_locale',
											'id'                          => 'selected_locale',
											'selected'                    => $locale,
											'languages'                   => $languages,
											'translations'                => $translations,
											'show_available_translations' => current_user_can( 'install_languages' ) && wp_can_install_language_pack(),
										)
									);
									?>
								</div>

								<span class="sui-description"><?php printf( esc_html__( 'Your %1$sWordPress Language Settings%2$s are set to %3$s .', 'wpmudev' ), '<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">', '</a>', esc_html( $current_native_language ) ); ?></span>
							</div>

						</div>
						<div class="sui-box-settings-row">

							<div class="sui-box-settings-col-1">

								<span class="sui-settings-label"><?php esc_html_e( 'Updates', 'wpmudev' ); ?></span>

								<span class="sui-description"><?php esc_html_e( 'Update old translations or choose to have WPMU DEV automatically download and install translation updates for you.', 'wpmudev' ); ?></span>

							</div>

							<div class="sui-box-settings-col-2">
								<?php
								$translation_update_count = count( $translation_update );
								if ( $translation_update_count > 0 ) :
									?>
									<div class="sui-notice sui-notice-warning">
										<div class="sui-notice-content">
											<div class="sui-notice-message">

												<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>

												<p><?php esc_html_e( sprintf( 'You have %d translations ready to update.', $translation_update_count ), 'wpmudev' ); ?></p>
												<button
													id="update_translations"
													data-modal-open="update-translation-modal"
													data-modal-mask="true"
													data-replace="false"
													class="sui-button"
												>
													<i class="sui-icon-update" aria-hidden="true"></i>
													<?php esc_html_e( 'Update Translations', 'wpmudev' ); ?>
												</button>
											</div>
										</div>
									</div>
								<?php else : ?>
									<div class="sui-notice sui-notice-success">
										<div class="sui-notice-content">
											<div class="sui-notice-message">

												<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>

												<p><?php esc_html_e( 'All translations are up to date.', 'wpmudev' ); ?></p>
											</div>
										</div>
									</div>
								<?php endif; ?>
								<div class="translation-box">
									<label for="enable_auto_translation" class="sui-toggle">
										<input
											type="checkbox"
											value="1"
											name="enable_auto_translation"
											id="enable_auto_translation"
											aria-labelledby="enable_auto_translation-label"
											aria-describedby="enable_auto_translation-desc"
											<?php checked( $enable_auto_translation, '1' ); ?>
										/>
										<span class="sui-toggle-slider" aria-hidden="true"></span>
										<span id="enable_sso-label" class="sui-toggle-label">
											<?php esc_html_e( 'Automatically update translations.', 'wpmudev' ); ?>
										</span>
										<span id="enable_auto_translation-desc" class="sui-description">
											<?php esc_html_e( 'We’ll automatically download language files for each of the plugins you install.', 'wpmudev' ); ?>
										</span>
									</label>
								</div>
							</div>
						</div>

					</div>

					<div class="sui-box-footer">

						<div class="sui-actions-right">

							<button
								type="submit"
								name="status"
								value="settings"
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

				</form>

			</div>
		<?php endif; ?>
		<div class="sui-box js-sidenav-content" id="permissions" style="display: none;">

			<form
				method="POST"
				action="<?php echo esc_url( $urls->settings_url ) . '#permissions'; ?>"
			>

				<input
					type="hidden"
					name="action"
					value="permissions-setup"
				/>

				<?php wp_nonce_field( 'permissions-setup', 'hash' ); ?>

				<div class="sui-box-header">
					<h2 class="sui-box-title"><?php esc_html_e( 'Permissions', 'wpmudev' ); ?></h2>
				</div>

				<div class="sui-box-body">

					<div class="sui-box-settings-row">

						<div class="sui-box-settings-col-1">

							<span class="sui-settings-label"><?php esc_html_e( 'Visibility', 'wpmudev' ); ?></span>

							<span class="sui-description"><?php esc_html_e( 'By default, only the user who authenticated the WPMU DEV Dashboard can see it in the sidebar. Enable other admins to view it by adding them here.', 'wpmudev' ); ?></span>

						</div>

						<div class="sui-box-settings-col-2">

							<div class="dashui-list-items">

								<?php
								foreach ( $allowed_users as $user ) :

									$disabled = '';
									$remove_url = '';

									if ( $can_manage_users ) {

										$remove_url = wp_nonce_url(
											add_query_arg(
												array(
													'user'   => $user['id'],
													'action' => 'admin-remove',
												),
												$urls->settings_url . '#permissions'
											),
											'admin-remove',
											'hash'
										);

									} else {
										$disabled = ' disabled';
									}
									?>

									<div class="dashui-item">

										<div class="dashui-item-name">
											<span class="user-image-round">
												<?php echo get_avatar( $user['id'], 20, 'mystery' ); ?>
											</span>
											<span><?php echo esc_html( ucwords( $user['name'] ) ); ?></span>
											<?php if ( $user['is_me'] ) : ?>
												<span class="sui-tag sui-tag-sm"><?php esc_html_e( 'You', 'wpmudev' ); ?></span>
											<?php endif; ?>
										</div>

										<div class="dashui-item-email">
											<span><?php echo esc_html( $user['email'] ); ?></span>
											<?php if ( $user['is_me'] ) : ?>
												<span class="sui-tag sui-tag-sm"><?php esc_html_e( 'You', 'wpmudev' ); ?></span>
											<?php endif; ?>
										</div>

										<div class="dashui-item-action">

											<?php if ( $user['is_me'] ) { ?>

												<div
													class="sui-button-icon sui-tooltip disabled"
													data-tooltip="<?php esc_html_e( 'You cannot remove yourself', 'wpmudev' ); ?>"
												>
													<i class="sui-icon-trash" aria-hidden="true"></i>
												</div>

											<?php } else { ?>

												<a
													href="<?php echo esc_url( $remove_url ); ?>"
													class="sui-button-icon js-remove-user-permisssions<?php echo esc_attr( $disabled ); ?>"
												>
												<span class="sui-loading-text">
													<i class="sui-icon-trash" aria-hidden="true"></i>
												</span>
													<i class="sui-icon-loader sui-loading"></i>
												</a>

											<?php } ?>

										</div>

									</div>

								<?php endforeach; ?>

							</div>

							<button
								id="open-add-user"
								data-modal-open="wpmudev-add-user"
								data-modal-mask="true"
								data-replace="false"
								class="sui-button sui-button-ghost modal-open"
								<?php echo( ! $can_manage_users ? 'disabled="disabled"' : '' ); ?>
							>
								<i class="sui-icon-plus" aria-hidden="true"></i>
								<?php esc_html_e( 'ADD USER', 'wpmudev' ); ?>
							</button>


							<?php if ( ! $can_manage_users ) : ?>
								<div class="sui-notice" style="margin: 30px 0;">
									<div class="sui-notice-content">
										<div class="sui-notice-message">

											<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>

											<p><?php esc_html_e( 'To manage user permissions here you need to remove the constant WPMUDEV_LIMIT_TO_USER from your wp-config file.', 'wpmudev' ); ?></p>
										</div>
									</div>
								</div>

							<?php endif; ?>

						</div>

					</div>

				</div>

			</form>

		</div>
		<div class="sui-box js-sidenav-content" id="apikey" style="display: none;">

			<div class="sui-box-header">

				<h2 class="sui-box-title"><?php esc_html_e( 'API Key', 'wpmudev' ); ?></h2>

				<div class="sui-actions-right">

					<a
						href="<?php echo esc_url( $urls->hub_url_old . '/account' ); ?>"
						target="_blank"
						class="sui-button sui-button-ghost"
					>
						<i class="sui-icon-key" aria-hidden="true"></i>
						<?php esc_html_e( 'Manage API Key', 'wpmudev' ); ?>
					</a>

				</div>

			</div>

			<div class="sui-box-body">

				<p><?php esc_html_e( 'Your API Key is unique to your WPMU DEV account and is the connection between you and our servers, and your access to all our Pro plugins syncing with The Hub.', 'wpmudev' ); ?></p>

				<div class="sui-form-field ">

					<label class="sui-label" for="api_key"><?php esc_html_e( 'Active Key', 'wpmudev' ); ?></label>

					<div class="sui-control-with-icon">
						<input
							value="<?php echo esc_attr( strtolower( WPMUDEV_Dashboard::$api->get_key() ) ); ?>"
							class="sui-form-control"
							id="api_key"
							readonly="readonly"
						>
						<i class="sui-icon-key" aria-hidden="true"></i>
					</div>

				</div>
				<div class="sui-notice" style="margin: 30px 0;">
					<div class="sui-notice-content">
						<div class="sui-notice-message">
							<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
							<p><?php esc_html_e( 'Note: If you are experiencing issues connecting to WPMU DEV, resetting this key can sometimes fix issues. You can do this via the Manage API Key button above.', 'wpmudev' ); ?></p>
						</div>
					</div>
				</div>
			</div>

		</div>
		<div class="sui-box js-sidenav-content" id="data" style="display: none;">
			<form
				method="POST"
				action="<?php echo esc_url( $urls->settings_url ) . '#data'; ?>"
			>
				<input
					type="hidden"
					name="action"
					value="data-setup"
				/>
				<?php wp_nonce_field( 'data-setup', 'hash' ); ?>
				<div class="sui-box-header">
					<h2 class="sui-box-title"><?php esc_html_e( 'Data & Settings', 'wpmudev' ); ?></h2>
				</div>
				<div class="sui-box-body">

					<p><?php esc_html_e( 'Control what to do with your settings and data. Settings are each module’s configuration options. Data includes stored information, such as logs, statistics and other bits of information stored over time.', 'wpmudev' ); ?></p>

					<?php
					$this->render(
						'sui/data-settings/uninstall',
						array(
							'keep_data'         => $keep_data,
							'preserve_settings' => $preserve_settings,
						)
					);
					?>

					<?php $this->render( 'sui/data-settings/reset' ); ?>
				</div>
				<div class="sui-box-footer">
					<div class="sui-actions-right">
						<button
							type="submit"
							name="status"
							value="settings"
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
			</form>
		</div>

	</div>

<?php
$this->render( 'sui/element-last-refresh' );

$this->render( 'sui/footer' );
?>
	<!-- add admin modal -->
<?php
$this->render(
	'sui/permission-settings/user-select-modal',
	array(
		'urls'            => $urls,
		'allowed_users'   => $allowed_users,
		'available_users' => $available_users,
	)
);
?>

<?php
$this->render(
	'sui/data-settings/reset-confirm-modal',
	array(
		'urls' => $urls,
	)
);
?>

	<div class="sui-hidden">
		<div class="js-notifications">
			<div class="sui-notice-top sui-notice-success sui-can-dismiss js-translation-updated">
				<div class="sui-notice-content">
					<p class="js-custom-message"></p>
				</div>
				<span class="sui-notice-dismiss">
				<a role="button" aria-label="Dismiss" class="sui-icon-check"></a>
			</span>
			</div>
		</div>
	</div>
<?php
if ( ! empty( $translation_update ) ) {
	$this->render( 'sui/popup-translation-details', compact( 'translation_update' ) );
}
