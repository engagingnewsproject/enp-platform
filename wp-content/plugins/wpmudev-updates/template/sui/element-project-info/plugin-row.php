<?php
/**
 * Template for the actual row content in plugins page.
 *
 * @package template
 */

?>
<div class="js-plugin-box"
	<?php foreach ( $attr as $key => $item ) : ?>
		data-<?php echo esc_attr( $key ); ?>="<?php echo esc_attr( $item ); ?>"
	<?php endforeach; ?>
>

	<?php
	/**
	 * ROW FOR PLUGIN LIST TABLE
	 */
	if ( false === isset( $hide_row ) ) {
		$hide_row = false;
	}
	if ( ! $hide_row ) :
		?>
		<div class="js-mode-row">

			<table class="sui-table">

				<tr
					data-project="<?php echo esc_attr( $pid ); ?>"
					id="project-row-<?php echo esc_attr( $pid ); ?>"
					class="<?php echo ! $res->is_installed ? esc_attr( 'dashui-is-notinstalled' ) : ''; ?> <?php echo $res->has_update ? esc_attr( 'dashui-plugin-hasupdate' ) : ''; ?> <?php echo ! $res->is_active ? esc_attr( 'dashui-plugin-notactive' ) : ''; ?>"
				>

					<td class="dashui-column-title">

						<div class="dashui-plugin-title">

							<label for="bulk-action-<?php echo esc_attr( $pid ); ?>" class="sui-checkbox">
								<input
									type="checkbox"
									name="pids[]"
									value="<?php echo esc_attr( $pid ); ?>"
									id="bulk-action-<?php echo esc_attr( $pid ); ?>"
									class="js-plugin-check"
								/>
								<span aria-hidden="true"></span>
								<span class="sui-screen-reader-text"><?php printf( '%s %s', esc_html_e( 'Select this plugin ', 'wpmudev' ), $res->name ); ?></span>
							</label>

							<div class="dashui-plugin-image plugin-image"
							     style="position:relative;">
								<?php if ( $res->has_update || ! $res->is_installed ): ?>
									<?php echo $res->has_update ? '<span class="dashui-update-dot" aria-hidden="true"></span>' : ''; ?>
									<img
										src="<?php echo esc_url( empty( $res->url->icon ) ? $res->url->thumbnail_square : $res->url->icon ); ?>"
										class="sui-image plugin-image js-show-plugin-modal"
										style="width:30px;height:30px; border-radius: 5px;"
										aria-hidden="true"
										data-action="<?php echo $res->has_update ? 'changelog' : 'info'; ?>"
										data-project="<?php echo esc_attr( $pid ); ?>"
									>
								<?php else: ?>
									<a href="<?php echo esc_url( $res->url->config ); ?>">
										<img
											src="<?php echo esc_url( $res->url->icon ); ?>"
											class="sui-image plugin-image"
											aria-hidden="true"
											style="width:30px;height:30px; border-radius: 5px;"
											data-project="<?php echo esc_attr( $pid ); ?>"
										>
										<span class="sui-screen-reader-text"><?php printf( '%s %s', $res->name, esc_html_e( ' settings', 'wpmudev' ) ); ?></span>
									</a>
								<?php endif; ?>
							</div>
							<?php if ( $res->has_update || ! $res->is_installed ): ?>
								<button
									class="dashui-plugin-name js-show-plugin-modal"
									id="show-modal-<?php echo esc_attr( $pid ); ?>"
									data-action="<?php echo $res->has_update ? 'changelog' : 'info'; ?>"
									data-project="<?php echo esc_attr( $pid ); ?>"
								>
									<?php
									if ( $res->is_installed ):
										printf( '%s <span class="sui-tag sui-tag-sm" style="margin-left:10px;">v%s</span>', esc_html( $res->name ), esc_html( $res->version_installed ) );
									else:
										echo esc_html( $res->name );
									endif; ?>
									<div class="dashui-desktop-hidden" style="display:inline-block; margin-left:5px;">
										<?php if ( $res->has_update ) { ?>
											<a
												href="#"
												id="show-modal-<?php echo esc_attr( $pid ); ?>"
												class="js-show-plugin-modal"
												data-action="<?php echo $res->has_update ? 'changelog' : 'info'; ?>"
												data-project="<?php echo esc_attr( $pid ); ?>"
											>
												<?php printf( '<span class="sui-tag sui-tag-sm sui-tag-yellow" style="cursor:pointer;">v%s %s</span>', esc_html( $res->version_latest ), esc_html__( 'update available', 'wpmudev' ) ); ?>
											</a>
										<?php } elseif ( $res->is_active ) { ?>
											<div class="dashui-loader-wrap">
												<div class="dashui-loader-text">
													<span class="sui-tag sui-tag-sm sui-tag-blue sui-loading-text"> <?php esc_html_e( 'Active', 'wpmudev' ); ?></span>
												</div>
												<div class="dashui-loader" style="display: none;">
													<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Deactivating...', 'wpmudev' ); ?></p>
												</div>
											</div>
										<?php } elseif ( $res->is_installed ) { ?>
											<div class="dashui-loader-wrap">
												<div class="dashui-loader-text">
													<span class="sui-tag sui-tag-sm sui-loading-text"> <?php esc_html_e( 'Inactive', 'wpmudev' ); ?> </span>
												</div>
												<div class="dashui-loader" style="display: none;">
													<div class="dashui-loader-activate">
														<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Activating...', 'wpmudev' ); ?></p>
													</div>
													<div class="dashui-loader-delete">
														<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Deleting...', 'wpmudev' ); ?></p>
													</div>
												</div>

											</div>
										<?php } ?>
									</div>
								</button>
							<?php else : ?>
								<div class="dashui-plugin-name">
									<?php if ( ! empty( $res->url->config ) ) : ?>
										<a href="<?php echo esc_url( $res->url->config ); ?>">
											<?php echo esc_html( $res->name ); ?>
										</a>
									<?php else : ?>
										<?php echo esc_html( $res->name ); ?>
									<?php endif; ?>
									<a
										href="#"
										class="js-show-plugin-modal"
										id="show-modal-<?php echo esc_attr( $pid ); ?>"
										data-action="changelog"
										data-project="<?php echo esc_attr( $pid ); ?>">
										<span class="sui-tag sui-tag-sm" style="margin-left:10px; cursor:pointer;">v<?php echo $res->version_installed; ?></span>
										<span class="sui-screen-reader-text"><?php esc_html_e( 'Show changelog', 'wpmudev' ); ?></span>
									</a>
									<div class="dashui-desktop-hidden" style="display:inline-block; margin-left:5px;">
										<?php if ( $res->has_update ) { ?>
											<a
												href="#"
												class="js-show-plugin-modal"
												id="show-modal-<?php echo esc_attr( $pid ); ?>"
												data-action="<?php echo $res->has_update ? 'changelog' : 'info'; ?>"
												data-project="<?php echo esc_attr( $pid ); ?>"
											>
												<?php printf( '<span class="sui-tag sui-tag-sm sui-tag-yellow" style="cursor:pointer;">v%s %s</span>', esc_html( $res->version_latest ), esc_html__( 'update available', 'wpmudev' ) ); ?>
											</a>
										<?php } elseif ( $res->is_active ) { ?>
											<div class="dashui-loader-wrap">
												<div class="dashui-loader-text">
													<span class="sui-tag sui-tag-sm sui-tag-blue sui-loading-text"> <?php esc_html_e( 'Active', 'wpmudev' ); ?></span>
												</div>
												<div class="dashui-loader" style="display: none;">
													<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Deactivating...', 'wpmudev' ); ?></p>
												</div>
											</div>
										<?php } elseif ( $res->is_installed ) { ?>
											<div class="dashui-loader-wrap">
												<div class="dashui-loader-text">
													<span class="sui-tag sui-tag-sm sui-loading-text"> <?php esc_html_e( 'Inactive', 'wpmudev' ); ?> </span>
												</div>
												<div class="dashui-loader" style="display: none;">
													<div class="dashui-loader-activate">
														<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Activating...', 'wpmudev' ); ?></p>
													</div>
													<div class="dashui-loader-delete">
														<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Deleting...', 'wpmudev' ); ?></p>
													</div>
												</div>

											</div>
										<?php } ?>
									</div>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $incompatible_reason ) || ! empty( $actions ) ) { ?>
								<div class="dashui-plugin-actions dashui-desktop-hidden" style="display:inline-flex">
									<div class="dashui-mobile-main-action" style="width:60px">
										<?php
										// Primary action button.
										if ( ! empty( $main_action ) ) :
											?>

											<a
												href="<?php echo esc_url( $main_action['url'] ); ?>"
												class="sui-button <?php echo esc_attr( $main_action_class ); ?>"
												data-type="<?php echo esc_attr( $main_action['type'] ); ?>"
												<?php if ( isset( $main_action['data'] ) && is_array( $main_action['data'] ) ) : ?>
													<?php foreach ( $main_action['data'] as $key_attr => $data_attr ) : ?>
														data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
													<?php endforeach; ?>
												<?php endif; ?>
											>

												<?php if ( 'sui-button-icon' !== $main_action_class ) : ?>
													<span class="sui-loading-text">
													<?php if ( $main_action['icon'] ) : ?>
														<i class="<?php echo esc_attr( $main_action['icon'] ); ?>"></i>
													<?php endif; ?>

														<?php echo esc_html( $main_action['name'] ); ?>
												</span>
													<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

												<?php else : ?>

													<?php if ( $main_action['icon'] ) : ?>
														<i class="<?php echo esc_attr( $main_action['icon'] ); ?>"></i>
													<?php endif; ?>

												<?php endif; ?>

											</a>

										<?php endif; ?>
									</div>

									<?php
									// Secondary action button.
									if ( ! empty( $actions ) ) :
										?>

										<?php
										// Single action button.
										if ( 1 === count( $actions ) ) { ?>

											<?php $plugin_action = reset( $actions ); ?>

											<?php if ( $plugin_action['icon'] ) : ?>

												<a
													href="<?php echo esc_url( $plugin_action['url'] ); ?>"
													class="sui-button-icon sui-button-blue sui-tooltip"
													data-tooltip="<?php echo esc_attr( $plugin_action['name'] ); ?>"
													data-type="<?php echo esc_attr( $plugin_action['type'] ); ?>"
													<?php if ( isset( $plugin_action['data'] ) && is_array( $plugin_action['data'] ) ) : ?>
														<?php foreach ( $plugin_action['data'] as $key_attr => $data_attr ) : ?>
															data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
														<?php endforeach; ?>
													<?php endif; ?>
												>

												<span class="sui-loading-text">
													<i class="<?php echo esc_attr( $plugin_action['icon'] ); ?>"></i>
												</span>

													<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>
												</a>

											<?php endif; ?>

											<?php
											// Multiple actions dropdown.
										} else {
											?>

											<div class="sui-dropdown">

												<button
													class="sui-button-icon sui-dropdown-anchor js-dropdown-actions"
													data-project="<?php echo esc_attr( $pid ); ?>"
												>

												<span class="sui-loading-text">
													<i class="<?php echo esc_attr( $actions_icon ); ?>"></i>
												</span>

													<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

												</button>

												<ul><?php foreach ( $actions as $plugin_action ) : ?>

														<li><a
																href="<?php echo esc_url( $plugin_action['url'] ); ?>"
																<?php if ( isset( $plugin_action['class'] ) ) : ?>
																	class="<?php echo esc_attr( $plugin_action['class'] ); ?>"
																<?php endif; ?>
																data-tooltip="<?php echo esc_attr( $plugin_action['name'] ); ?>"
																data-type="<?php echo esc_attr( $plugin_action['type'] ); ?>"
																<?php if ( isset( $plugin_action['data'] ) && is_array( $plugin_action['data'] ) ) : ?>
																	<?php foreach ( $plugin_action['data'] as $key_attr => $data_attr ) : ?>
																		data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
																	<?php endforeach; ?>
																<?php endif; ?>
															>
																<?php if ( $plugin_action['icon'] ) : ?>
																	<i class="<?php echo esc_attr( $plugin_action['icon'] ); ?>"></i>
																<?php endif; ?>
																<?php echo esc_html( $plugin_action['name'] ); ?>
															</a></li>

													<?php endforeach; ?></ul>

											</div>

										<?php } ?>

									<?php endif; ?>

								</div>

							<?php } ?>

						</div>

					</td>

					<?php if ( $res->is_installed ) : ?>
						<td class="dashui-column-actions plugin-row-actions dashui-mobile-hidden">
							<?php if ( $res->has_update ) { ?>
								<a
									href="#"
									class="js-show-plugin-modal"
									data-action="<?php echo $res->has_update ? 'changelog' : 'info'; ?>"
									data-project="<?php echo esc_attr( $pid ); ?>"
								>
									<?php printf( '<span class="sui-tag sui-tag-sm sui-tag-yellow" style="cursor:pointer;">v%s %s</span>', esc_html( $res->version_latest ), esc_html__( 'update available', 'wpmudev' ) ); ?>
								</a>
							<?php } elseif ( $res->is_active ) { ?>
								<div class="dashui-loader-wrap">
									<div class="dashui-loader-text">
										<span class="sui-tag sui-tag-sm sui-tag-blue sui-loading-text"> <?php esc_html_e( 'Active', 'wpmudev' ); ?></span>
									</div>
									<div class="dashui-loader" style="display: none;">
										<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Deactivating...', 'wpmudev' ); ?></p>
									</div>
								</div>
							<?php } else { ?>
								<div class="dashui-loader-wrap">
									<div class="dashui-loader-text">
										<span class="sui-tag sui-tag-sm sui-loading-text"> <?php esc_html_e( 'Inactive', 'wpmudev' ); ?> </span>
									</div>
									<div class="dashui-loader" style="display: none;">
										<div class="dashui-loader-activate">
											<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Activating...', 'wpmudev' ); ?></p>
										</div>
										<div class="dashui-loader-delete">
											<p class="sui-p-small"><i class="sui-icon-loader sui-loading" aria-hidden="true"></i><?php esc_html_e( 'Deleting...', 'wpmudev' ); ?></p>
										</div>
									</div>

								</div>
							<?php } ?>
						</td>
					<?php endif; ?>

					<?php if ( true === $allow_description ): ?>
						<?php $colspan = ''; ?>
						<?php if ( false === $res->is_installed && in_array( $membership_type, array( 'expired', 'paused' ), true ) ) {
							$colspan = 'colspan="2"';
						} ?>
						<td class="dashui-column-description plugin-row-info" <?php echo $colspan; ?>><?php echo esc_html( $res->info ); ?></td>
					<?php endif; ?>
					<?php if ( false === $res->is_installed && in_array( $membership_type, array( 'expired', 'paused' ), true ) ): ?>
						<?php // do nothing. ?>
					<?php elseif ( false === $res->is_installed && 'free' === $membership_type && ! $res->is_licensed ) : ?>
						<td class="plugin-row-actions plugin-row-actions-right">
						<span class="sui-tag sui-tag-pro">
							<?php esc_html_e( 'Pro', 'wpmudev' ); ?>
						</span>
						</td>
					<?php else : ?>
						<td class="dashui-column-actions plugin-row-actions">

							<div class="dashui-plugin-actions dashui-mobile-hidden">

								<?php
								// Show total number of installs.
								if ( $show_num_install ) {
									?>
									<strong><?php echo esc_html( sprintf( _n( '%s install', '%s installs', $num_install, 'wpmudev' ), $rounded_num_install ) ); ?></strong>
								<?php } ?>

								<?php // Plugin actions. ?>
								<div class="sui-actions-right">

									<?php
									// Primary action button.
									if ( ! empty( $main_action ) ) :
										$additional_classes = '';
										if ( ( in_array( $membership_type, array( 'expired', 'paused', 'free' ), true ) || ( $is_unit_membership && false === $is_unit_allowed ) ) && $res->is_installed && $res->has_update && ! $res->is_licensed ) {
											$additional_classes = ' main-action-free sui-tooltip-constrained sui-tooltip-top-right sui-tooltip ';
										}
										$href = '';
										if ( ( in_array( $membership_type, array( 'expired', 'paused', 'free' ), true ) || ( $is_unit_membership && false === $is_unit_allowed ) ) && $res->is_installed && $res->has_update && ! $res->is_licensed ) {
											$href = $reactivate_url;
										} else {
											$href = $main_action['url'];
										}
										?>

										<a
											href="<?php echo esc_attr( $href ); ?>"
											class="<?php echo esc_attr( $additional_classes ); ?> sui-button <?php echo esc_attr( $main_action_class ); ?>"
											<?php if ( in_array( $membership_type, array( 'expired', 'paused' ), true ) && $res->is_installed && $res->has_update ) : ?>
												<?php // translators: %s name of the plugin that is updated. ?>
												data-tooltip="<?php printf( esc_html__( 'Reactivate your membership to update %s and unlock pro features', 'wpmudev' ), esc_html( $res->name ) ); ?>"
												data-action="reactivate-membership"
											<?php else : ?>
												<?php if ( $res->is_installed && ! $res->is_licensed && ( ( false === $is_unit_allowed && $is_unit_membership ) || 'free' === $membership_type ) ) : ?>
													<?php // translators: %s name of the plugin that is updated. ?>
													data-tooltip="<?php printf( esc_html__( 'Upgrade your membership to update %s and unlock pro features', 'wpmudev' ), esc_html( $res->name ) ); ?>"
												<?php endif; ?>
												data-type="<?php echo esc_attr( $main_action['type'] ); ?>"
												<?php if ( isset( $main_action['data'] ) && is_array( $main_action['data'] ) ) : ?>
													<?php foreach ( $main_action['data'] as $key_attr => $data_attr ) : ?>
														data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
													<?php endforeach; ?>
												<?php endif; ?>
											<?php endif; ?>
										>

											<?php if ( 'sui-button-icon' !== $main_action_class ) : ?>
												<span class="sui-loading-text">
											<?php if ( $main_action['icon'] ) : ?>
												<i class="<?php echo esc_attr( $main_action['icon'] ); ?>"></i>
											<?php endif; ?>

													<?php echo esc_html( $main_action['name'] ); ?>
										</span>
												<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

											<?php else : ?>

												<?php if ( $main_action['icon'] ) : ?>
													<i class="<?php echo esc_attr( $main_action['icon'] ); ?>"></i>
												<?php endif; ?>

											<?php endif; ?>

										</a>

									<?php endif; ?>

									<?php
									// Incompatible notice.
									if ( ! empty( $incompatible_reason ) ) : ?>
										<span class="sui-tag sui-tag-sm sui-tag-red sui-tag-ghost"><?php echo esc_html( $incompatible_reason ); ?></span>
									<?php endif; ?>

									<?php
									// Secondary action button.
									if ( ! empty( $actions ) && false === $secondary_action_same_as_primary ) : ?>

										<?php
										// Single action button.
										if ( 1 === count( $actions ) ) {
											?>

											<?php $plugin_action = reset( $actions ); ?>

											<?php if ( $plugin_action['icon'] ) : ?>

												<a
													href="<?php echo esc_url( $plugin_action['url'] ); ?>"
													class="<?php echo $res->is_active ? 'sui-button-icon' : 'sui-button sui-button-blue'; ?>"
													data-type="<?php echo esc_attr( $plugin_action['type'] ); ?>"
													<?php if ( isset( $plugin_action['data'] ) && is_array( $plugin_action['data'] ) ) : ?>
														<?php foreach ( $plugin_action['data'] as $key_attr => $data_attr ) : ?>
															data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
														<?php endforeach; ?>
													<?php endif; ?>
												>

											<span class="sui-loading-text">
												<i class="<?php echo esc_attr( $plugin_action['icon'] ); ?>"></i>
												<?php
												if ( ! $res->is_active ) {
													echo esc_html( $plugin_action['name'] );
												}
												?>
											</span>

													<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

												</a>

											<?php endif; ?>

											<?php
											// Multiple actions dropdown.
										} else {
											?>

											<div class="sui-dropdown">

												<button
													class="sui-button-icon sui-dropdown-anchor js-dropdown-actions"
													data-project="<?php echo esc_attr( $pid ); ?>"
												>

											<span class="sui-loading-text">
												<i class="<?php echo esc_attr( $actions_icon ); ?>"></i>
											</span>

													<i class="sui-icon-loader sui-loading" aria-hidden="true"></i>

												</button>

												<ul
													<?php
													if ( in_array( $membership_type, array( 'expired', 'paused', 'free' ), true ) && $res->has_update && ! $res->is_licensed ) {
														echo 'class="reactivate-membership-dropdown"';
													}
													?>
												>
													<?php foreach ( $actions as $plugin_action ) : ?>
														<li>
															<?php if ( ! $res->is_licensed && in_array( $membership_type, array( 'expired', 'paused', 'free' ), true ) && ( $plugin_action['icon'] === 'sui-icon-download' ) ) : ?>
																<a
																	href="<?php echo 'free' === $membership_type ? esc_url( $url_upgrade ) : esc_url( $reactivate_url ); ?>"
																	class="reactivate-membership-dropdown-action"
																	data-action="<?php echo 'free' === $membership_type ? 'upgrade-membership' : 'reactivate-membership'; ?>"
																>
																	<?php if ( $plugin_action['icon'] ) : ?>
																		<i class="<?php echo esc_attr( $plugin_action['icon'] ); ?>"></i>
																	<?php endif; ?>
																	<?php 'free' === $membership_type ? esc_html_e( 'Upgrade Membership', 'wpmudev' ) : esc_html_e( 'Reactivate Membership', 'wpmudev' ); ?>
																</a>
															<?php else : ?>
																<a
																	href="<?php echo esc_url( $plugin_action['url'] ); ?>"
																	<?php if ( isset( $plugin_action['class'] ) ) : ?>
																		class="<?php echo esc_attr( $plugin_action['class'] ); ?>"
																	<?php endif; ?>
																	data-tooltip="<?php echo esc_attr( $plugin_action['name'] ); ?>"
																	data-type="<?php echo esc_attr( $plugin_action['type'] ); ?>"
																	<?php if ( isset( $plugin_action['data'] ) && is_array( $plugin_action['data'] ) ) : ?>
																		<?php foreach ( $plugin_action['data'] as $key_attr => $data_attr ) : ?>
																			data-<?php echo esc_attr( $key_attr ); ?>="<?php echo esc_attr( $data_attr ); ?>"
																		<?php endforeach; ?>
																	<?php endif; ?>
																>
																	<?php if ( $plugin_action['icon'] ) : ?>
																		<i class="<?php echo esc_attr( $plugin_action['icon'] ); ?>"></i>
																	<?php endif; ?>
																	<?php echo esc_html( $plugin_action['name'] ); ?>
																</a>
															<?php endif; ?>
														</li>
													<?php endforeach; ?>
												</ul>
											</div>

										<?php } ?>

									<?php endif; ?>

								</div>

							</div>

						</td>
					<?php endif; ?>

				</tr>

			</table>

		</div>
	<?php endif; ?>

</div>