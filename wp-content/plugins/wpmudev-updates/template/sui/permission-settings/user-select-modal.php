<?php
/**
 * User add modal content.
 *
 * @var array                           $allowed_users   Allowed users list.
 * @var array                           $available_users Available admin users.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls            URLs class;
 *
 * @package WPMUDEV_Dashboard
 * @since   4.11.2
 */

?>

<!-- add admin modal -->
<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="wpmudev-add-user"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="wpmudev-add-user-title"
		aria-describedby="wpmudev-add-user-desc"
	>
		<div class="sui-box" style="margin-bottom: 0;">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
				<button class="sui-button-icon sui-button-float--right" data-modal-close="">
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog.', 'wpmudev' ); ?></span>
				</button>
				<h3 id="wpmudev-add-user-title" class="sui-box-title sui-lg"><?php esc_html_e( 'Add User', 'wpmudev' ); ?></h3>
				<p id="wpmudev-add-user-desc" class="sui-description"><?php esc_html_e( 'Add as many administrators as you like. Only these specific users will see the WPMU DEV menu.', 'wpmudev' ); ?></p>
			</div>
			<form id="form-admin-add" action="<?php echo esc_url( $urls->settings_url ) . '#permissions'; ?>" method="POST">
				<div class="sui-box-body">
					<input type="hidden" name="action" value="admin-add"/>
					<?php wp_nonce_field( 'admin-add', 'hash' ); ?>
					<div class="sui-form-field">
						<label class="sui-label" for="search-user" id="search-user-label">
							<?php echo esc_html__( 'Search users', 'wpmudev' ); ?>
						</label>
						<div class="sui-control-with-icon">
							<span class="sui-icon-profile-male" aria-hidden="true"></span>
							<input
								placeholder="<?php esc_html_e( 'Type username', 'wpmudev' ); ?>"
								id="search-user"
								class="sui-form-control"
								aria-labelledby="search-user-label"
								aria-describedby="wpmudev-add-user-desc"
							/>
						</div>
					</div>
					<div class="sui-form-field">
						<label
							class="sui-label"
							for="wpmudev-permissions-users-added"
							id="wpmudev-permissions-users-added-label"
						>
							<?php echo esc_html__( 'Added admins', 'wpmudev' ); ?>
						</label>
						<div class="dashui-list-items dashui-list-items-gray" id="wpmudev-permissions-users-added">
							<?php if ( ! empty( $allowed_users ) ) : ?>
								<?php foreach ( $allowed_users as $user ) : ?>
									<div
										class="dashui-item permissions-user-item permissions-user-added"
										data-email="<?php echo esc_html( strtolower( $user['email'] ) ); ?>"
										data-firstname="<?php echo esc_html( strtolower( $user['first_name'] ) ); ?>"
										data-lastname="<?php echo esc_html( strtolower( $user['last_name'] ) ); ?>"
										data-name="<?php echo esc_html( strtolower( $user['name'] ) ); ?>"
										data-username="<?php echo esc_html( strtolower( $user['username'] ) ); ?>"
									>
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
											<?php if ( ! $user['is_me'] ) : ?>
												<button
													role="button"
													type="button"
													class="sui-button-icon sui-tooltip permissions-user-add sui-hidden"
													data-user="<?php echo esc_attr( $user['id'] ); ?>"
													data-tooltip="<?php echo esc_html__( 'Add User', 'wpmudev' ); ?>"
												>
													<span class="sui-icon-plus" aria-hidden="true"></span>
													<span class="sui-screen-reader-text">
													<?php echo esc_html__( 'Add User', 'wpmudev' ); ?>
												</span>
												</button>
											<?php endif; ?>
											<button
												role="button"
												type="button"
												data-user="<?php echo esc_attr( $user['id'] ); ?>"
												class="sui-button-icon sui-button-red sui-tooltip <?php echo $user['is_me'] ? 'disabled' : 'permissions-user-remove'; ?>"
												data-tooltip="<?php $user['is_me'] ? esc_html_e( 'You cannot remove yourself', 'wpmudev' ) : esc_html_e( 'Remove User', 'wpmudev' ); ?>"
											>
												<span class="sui-icon-trash" aria-hidden="true"></span>
												<span class="sui-screen-reader-text">
													<?php esc_html_e( 'Remove User', 'wpmudev' ); ?>
												</span>
											</button>
										</div>
										<input type="hidden" name="users[]" class="user-id-hidden" value="<?php echo esc_attr( $user['id'] ); ?>"/>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
					</div>

					<div class="sui-form-field">
						<label
							class="sui-label"
							for="wpmudev-permissions-users-all"
							id="wpmudev-permissions-users-all-label"
						>
							<?php echo esc_html__( 'All admins', 'wpmudev' ); ?>
						</label>
						<div class="dashui-list-items dashui-list-items-gray" id="wpmudev-permissions-users-all">
							<?php if ( ! empty( $available_users ) ) : ?>
								<?php foreach ( $available_users as $user ) : ?>
									<div
										class="dashui-item permissions-user-item permissions-user-available"
										data-email="<?php echo esc_html( strtolower( $user->user_email ) ); ?>"
										data-name="<?php echo esc_html( strtolower( $user->display_name ) ); ?>"
										data-username="<?php echo esc_html( strtolower( $user->user_login ) ); ?>"
									>
										<div class="dashui-item-name">
											<span class="user-image-round">
												<?php echo get_avatar( $user->ID, 20, 'mystery' ); ?>
											</span>
											<span><?php echo esc_html( ucwords( $user->display_name ) ); ?></span>
										</div>
										<div class="dashui-item-email">
											<span><?php echo esc_html( $user->user_email ); ?></span>
										</div>
										<div class="dashui-item-action">
											<button
												role="button"
												type="button"
												data-user="<?php echo intval( $user->ID ); ?>"
												class="sui-button-icon sui-button-red sui-hidden sui-tooltip permissions-user-remove"
												data-tooltip="<?php esc_html_e( 'Remove User', 'wpmudev' ); ?>"
											>
												<span class="sui-icon-trash" aria-hidden="true"></span>
												<span class="sui-screen-reader-text">
													<?php esc_html_e( 'Remove User', 'wpmudev' ); ?>
												</span>
											</button>
											<button
												role="button"
												type="button"
												class="sui-button-icon sui-tooltip permissions-user-add"
												data-user="<?php echo intval($user->ID ); ?>"
												data-tooltip="<?php echo esc_html__( 'Add User', 'wpmudev' ); ?>"
											>
												<span class="sui-icon-plus" aria-hidden="true"></span>
												<span class="sui-screen-reader-text">
													<?php echo esc_html__( 'Add User', 'wpmudev' ); ?>
												</span>
											</button>
										</div>
									</div>
								<?php endforeach; ?>
							<?php endif; ?>
						</div>
						<div
							id="permissions-users-all-added-notice"
							class="sui-notice <?php echo empty( $available_users ) ? '' : 'sui-hidden'; ?>"
						>
							<div class="sui-notice-content">
								<div class="sui-notice-message">
									<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
									<p><?php esc_html_e( 'All admins have been added to the list.', 'wpmudev' ); ?></p>
								</div>
							</div>
						</div>
						<div
							id="permissions-users-empty-results-notice"
							class="sui-notice sui-hidden"
						>
							<div class="sui-notice-content">
								<div class="sui-notice-message">
									<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
									<p><?php esc_html_e( 'No users found.', 'wpmudev' ); ?></p>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="sui-box-footer sui-flatten sui-content-separated">
					<button type="button" class="sui-button sui-button-ghost" data-modal-close="">
						<?php esc_html_e( 'Cancel', 'wpmudev' ); ?>
					</button>
					<button
						type="submit"
						class="sui-button sui-button-blue"
						id="permissions-users-save"
						disabled
					>
						<span class="sui-loading-text"><?php esc_html_e( 'Save', 'wpmudev' ); ?></span>
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
