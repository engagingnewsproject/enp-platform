<?php
/**
 * Lazy load: meta box.
 *
 * @since 2.5.0
 * @package Hummingbird
 *
 * @var bool $is_enabled Comment lazy load status.
 * @var string $method Lazy Load method - Click, Scroll
 * @var array $button Button - Dimension, Color, Alignment
 * @var int $threshold Minimum comment count to lazy load
 * @var string $smush_activate_url URL to activate Smush Free version.
 * @var string $smush_activate_pro_url URL to activate Smush Pro version.
 * @var string $activate_smush_lazy_load_url URL to activate Lazy Load in Smush.
 * @var bool $is_smush_lazy_load_configurable Can the user activate Smush.
 * @var bool $is_smush_active Smush Activation status.
 * @var bool $is_smush_installed Smush Installation status.
 * @var bool $is_smush_pro Smush Pro status.
 * @var bool $smush_lazy_load Lazy enabled in smush or not
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box-settings-row">
	<p>
		<?php esc_html_e( 'Lazy loading delays loading specific content to speed up the overall pagespeed. In particular, sites with lots of comments, iframes and images can slow down over time.', 'wphb' ); ?>
	</p>
</div>

<div class="sui-box-settings-row">
	<div class="sui-box-settings-col-1">
		<span class="sui-settings-label"><?php esc_html_e( 'Comments', 'wphb' ); ?></span>
		<span class="sui-description">
			<?php esc_html_e( 'Loading lots of comments using the native WordPress comment system can delay your page speed dramatically. Enabling this feature will boost your page speed on pages with lots of comments.', 'wphb' ); ?>
		</span>
	</div>
	<div class="sui-box-settings-col-2">
		<label for="lazy_load" class="sui-toggle">
			<input type="checkbox" name="lazy_load" id="lazy_load" aria-controls="lazy_load-method" aria-labelledby="lazy-load-label" <?php checked( $is_enabled ); ?> />
			<span class="sui-toggle-slider" aria-hidden="true"></span>
			<span id="lazy-load-label" class="sui-toggle-label"><?php esc_html_e( 'Enable lazy loading comments', 'wphb' ); ?></span>
		</label>
		<div class="sui-border-frame sui-toggle-content<?php echo $is_enabled ? '' : ' sui-hidden'; ?>" id="wphb-lazy-load-comments-wrap">

			<div tabindex="0" id="lazy_load-method" aria-label="<?php esc_html_e( 'Choose how you want your comments to be lazy loaded.', 'wphb' ); ?>">
				<span><?php esc_html_e( 'Method', 'wphb' ); ?></span>
				<span class="sui-description" id="wphb-load-method-desc"><?php esc_html_e( 'Choose how you want your comments to be lazy loaded.', 'wphb' ); ?></span>

				<div class="sui-side-tabs sui-tabs">
					<div data-tabs class="sui-tabs-menu">
						<label for="load-on-click" class="sui-tab-item<?php echo checked( 'click', $method ) ? ' active' : ''; ?>">
							<input type="radio" name="method" value="click" id="load-on-click" <?php checked( 'click', $method, true ); ?>>
							<?php esc_html_e( 'On click', 'wphb' ); ?>
						</label>
						<label for="load-on-scroll" class="sui-tab-item<?php echo checked( 'scroll', $method ) ? ' active' : ''; ?>">
							<input type="radio" name="method" value="scroll" id="load-on-scroll" <?php checked( 'scroll', $method, true ); ?>>
							<?php esc_html_e( 'On scroll', 'wphb' ); ?>
						</label>
					</div>

					<div class="sui-tabs-content" data-panes>
						<div id="on-click-settings" class="sui-tab-boxed sui-toggle-content<?php echo checked( 'click', $method ) ? ' active' : ''; ?>">
							<span class="sui-description"><?php esc_html_e( 'This method will require the visitor to click a button to load up the comments and is the most supported method for most websites.', 'wphb' ); ?></span>
							<span id="button-style-header"><?php esc_html_e( 'Button styling', 'wphb' ); ?></span>
							<div class="sui-tabs">
								<div role="tablist" class="sui-tabs-menu" id="wphb-button-styling">
									<button type="button" role="tab" id="button-dimensions" class="sui-tab-item active" aria-controls="tab-content-btn-dimension" aria-selected="true">
										<?php esc_html_e( 'DIMENSIONS', 'wphb' ); ?>
									</button>
									<button type="button" role="tab" id="button-color" class="sui-tab-item" aria-controls="tab-content-btn-color" aria-selected="false" tabindex="-1">
										<?php esc_html_e( 'COLOR', 'wphb' ); ?>
									</button>
									<button type="button" role="tab" id="button-alignment" class="sui-tab-item" aria-controls="tab-content-btn-alignment" aria-selected="false" tabindex="-1">
										<?php esc_html_e( 'ALIGNMENT', 'wphb' ); ?>
									</button>
								</div>

								<div class="sui-tabs-content">
									<div role="tabpanel" tabindex="0" id="tab-content-btn-dimension" class="sui-tab-content active" aria-labelledby="button-dimensions">
										<div class="sui-row">
											<div class="sui-col-md-5">
												<div class="sui-form-field">
													<label for="button_height" class="sui-label" id="button_height_label"><?php esc_html_e( 'Height', 'wphb' ); ?>
														<span class="sui-label-note">px</span>
													</label>
													<input id="button_height" class="sui-form-control" aria-labelledby="button_height_label" name="button[dimensions][height]"
														value="<?php echo $button['dimensions']['height'] ? (int) $button['dimensions']['height'] : ''; ?>" placeholder="30">
												</div>
											</div>
											<div class="sui-col-md-5">
												<div class="sui-form-field">
													<label for="button_width" id="button_width_label" class="sui-label"><?php esc_html_e( 'Width', 'wphb' ); ?>
														<span class="sui-label-note">px</span>
													</label>
													<input id="button_width" class="sui-form-control" aria-labelledby="button_width_label" name="button[dimensions][width]"
														value="<?php echo $button['dimensions']['width'] ? (int) $button['dimensions']['width'] : ''; ?>" placeholder="130">
												</div>
											</div>
										</div>
										<div class="sui-row">
											<div class="sui-col-md-5">
												<div class="sui-form-field">
													<label for="button_radius" id="button_radius_label" class="sui-label"><?php esc_html_e( 'Border radius', 'wphb' ); ?></label>
													<input id="button_radius" class="sui-form-control" aria-labelledby="button_radius_label" name="button[dimensions][radius]"
														value="<?php echo $button['dimensions']['radius'] ? (int) $button['dimensions']['radius'] : ''; ?>" placeholder="4">
												</div>
											</div>
										</div>
									</div>

									<div role="tabpanel" tabindex="0" id="tab-content-btn-color" class="sui-tab-content sui-form-field" aria-labelledby="button-color" hidden>
										<div class="sui-form-field sui-col-md-6">
											<label class="sui-label" id="background-color-label" for="lazy-load-color-button-background">
												<?php esc_html_e( 'Background color', 'wphb' ); ?>
											</label>
											<div class="sui-colorpicker-wrap">
												<div class="sui-colorpicker" aria-hidden="true">
													<div class="sui-colorpicker-value">
														<span role="button">
															<span style="background-color:<?php echo esc_attr( $button['color']['background'] ); ?>"></span>
														</span>
														<input type="text" class="sui-input-md" value="<?php echo esc_attr( $button['color']['background'] ); ?>" readOnly="readonly">
														<button type="button">
															<span class="sui-icon-close" aria-hidden="true"></span>
														</button>
													</div>
													<button class="sui-button"><?php esc_html_e( 'Select', 'wphb' ); ?></button>
												</div>
												<input type="text" aria-labelledby="background-color-label" id="lazy-load-color-button-background" class="sui-colorpicker-input"
													data-alpha="false" data-attribute="<?php echo esc_attr( $button['color']['background'] ); ?>" name="button[color][background]"
													value="<?php echo esc_attr( $button['color']['background'] ); ?>">
											</div>
										</div>
										<div class="sui-form-field sui-col-md-6">
											<label class="sui-label" for="lazy-load-color-button-border" id="border-color-label">
												<?php esc_html_e( 'Border color', 'wphb' ); ?>
											</label>
											<div class="sui-colorpicker-wrap">
												<div class="sui-colorpicker" aria-hidden="true">
													<div class="sui-colorpicker-value">
														<span role="button">
															<span style="background-color:<?php echo esc_attr( $button['color']['border'] ); ?>"></span>
														</span>
														<input type="text" class="sui-input-md" value="<?php echo esc_attr( $button['color']['border'] ); ?>" readOnly="readonly">
														<button type="button">
															<span class="sui-icon-close" aria-hidden="true"></span>
														</button>
													</div>
													<button class="sui-button"><?php esc_html_e( 'Select', 'wphb' ); ?></button>
												</div>
												<input type="text" aria-labelledby="background-color-label" id="lazy-load-color-button-border" class="sui-colorpicker-input"
													data-alpha="false" data-attribute="<?php echo esc_attr( $button['color']['border'] ); ?>" name="button[color][border]"
													value="<?php echo esc_attr( $button['color']['border'] ); ?>">
											</div>
										</div>
										<div class="sui-form-field sui-col-md-6">
											<label class="sui-label" for="lazy-load-color-button-hover" id="lazy-load-color-button-hover-label">
												<?php esc_html_e( 'Hover', 'wphb' ); ?>
											</label>
											<div class="sui-colorpicker-wrap">
												<div class="sui-colorpicker" aria-hidden="true">
													<div class="sui-colorpicker-value">
														<span role="button">
															<span style="background-color:<?php echo esc_attr( $button['color']['hover'] ); ?>"></span>
														</span>
														<input type="text" value="<?php echo esc_attr( $button['color']['hover'] ); ?>" readOnly="readonly" />
														<button type="button">
															<span class="sui-icon-close" aria-hidden="true"></span>
														</button>
													</div>
													<button class="sui-button"><?php esc_html_e( 'Select', 'wphb' ); ?></button>
												</div>
												<input aria-labelledby="lazy-load-color-button-hover-label" id="lazy-load-color-button-hover" class="sui-colorpicker-input wp-color-picker"
													data-alpha="false" data-attribute="<?php echo esc_attr( $button['color']['hover'] ); ?>" name="button[color][hover]"
													value="<?php echo esc_attr( $button['color']['hover'] ); ?>">
											</div>
										</div>
									</div>

									<div role="tabpanel" tabindex="0" id="tab-content-btn-alignment" class="sui-tab-content" aria-labelledby="button-alignment" hidden>
										<div class="sui-row">
											<div class="sui-col-md-5">
												<div class="sui-form-field">
													<label id="button_align" class="sui-label"><?php esc_html_e( 'Align', 'wphb' ); ?></label>
													<div class="sui-side-tabs">
														<div class="sui-tabs-menu" id="align-options">
															<label for="align-left" class="sui-tab-item<?php echo checked( 'left', $button['alignment']['align'] ) ? ' active' : ''; ?>">
																<span class="icon-align sui-icon-align-x-left" aria-hidden="true"></span>
																<input type="radio" name="button[alignment][align]" value="left" id="align-left" <?php checked( 'left', $button['alignment']['align'], true ); ?> name="button[alignment][align]">
															</label>
															<label for="align-center" class="sui-tab-item<?php echo checked( 'center', $button['alignment']['align'] ) ? ' active' : ''; ?>">
																<span class="icon-align sui-icon-align-x-center" aria-hidden="true"></span>
																<input type="radio" name="button[alignment][align]" value="center" id="align-center" <?php checked( 'center', $button['alignment']['align'], true ); ?> name="button[alignment][align]">
															</label>
															<label for="align-right" class="sui-tab-item<?php echo checked( 'right', $button['alignment']['align'] ) ? ' active' : ''; ?>">
																<span class="icon-align sui-icon-align-x-right" aria-hidden="true"></span>
																<input type="radio" name="button[alignment][align]" value="right" id="align-right" <?php checked( 'right', $button['alignment']['align'], true ); ?> name="button[alignment][align]">
															</label>
														</div>
													</div>
												</div>
											</div>
											<div class="sui-col-md-5">
												<div class="sui-form-field">
													<label for="button-width-block" id="button-width-block-label" class="sui-checkbox">
														<input type="checkbox" id="button-width-block" aria-labelledby="button-width-block-label" <?php checked( 'on', $button['alignment']['full_width'] ); ?> name="button[alignment][full_width]"/>
														<span aria-hidden="true"></span>
														<span ><?php esc_html_e( 'Full width block', 'wphb' ); ?></span>
													</label>
												</div>
											</div>
										</div>
										<div class="sui-tabs-content">
											<div class="sui-row">
												<div class="sui-col-md-5">
													<div class="sui-form-field">
														<label for="button_margin_l" id="button_margin_l_label" class="sui-label"><?php esc_html_e( 'Margin left', 'wphb' ); ?>
															<span class="sui-label-note">px</span>
														</label>
														<input id="button_margin_l" class="sui-form-control" aria-labelledby="button_margin_l_label" name="button[alignment][left]"
															value="<?php echo $button['alignment']['left'] ? (int) $button['alignment']['left'] : ''; ?>" placeholder="0">
													</div>
												</div>
												<div class="sui-col-md-5">
													<div class="sui-form-field">
														<label for="button_margin_r" id="button_margin_r_label" class="sui-label"><?php esc_html_e( 'Margin right', 'wphb' ); ?>
															<span class="sui-label-note">px</span>
														</label>
														<input id="button_margin_r" class="sui-form-control" aria-labelledby="button_margin_r_label" name="button[alignment][right]"
															value="<?php echo $button['alignment']['right'] ? (int) $button['alignment']['right'] : ''; ?>" placeholder="0">
													</div>
												</div>
											</div>

											<div class="sui-row">
												<div class="sui-col-md-5">
													<div class="sui-form-field">
														<label for="button_margin_t" id="button_margin_t_label" class="sui-label"><?php esc_html_e( 'Margin top', 'wphb' ); ?>
															<span class="sui-label-note">px</span>
														</label>
														<input id="button_margin_t" class="sui-form-control" aria-labelledby="button_margin_t_label" name="button[alignment][top]"
															value="<?php echo $button['alignment']['top'] ? (int) $button['alignment']['top'] : ''; ?>" placeholder="150">
													</div>
												</div>
												<div class="sui-col-md-5">
													<div class="sui-form-field">
														<label for="button_margin_b" id="button_margin_b_label" class="sui-label"><?php esc_html_e( 'Margin bottom', 'wphb' ); ?>
															<span class="sui-label-note">px</span>
														</label>
														<input id="button_margin_b" class="sui-form-control" aria-labelledby="button_margin_b_label" name="button[alignment][bottom]"
															value="<?php echo $button['alignment']['bottom'] ? (int) $button['alignment']['bottom'] : ''; ?>" placeholder="0">
													</div>
												</div>
											</div>
										</div>
									</div>

								</div>
							</div>
						</div><!-- End of tab content for on click method -->
						<div id="on-scroll-settings" class="sui-tab-boxed sui-toggle-content<?php echo checked( 'scroll', $method ) ? ' active' : ''; ?>">
							<span class="sui-description"><?php esc_html_e( 'This method will automatically load the comments section when it scrolls into view. The new comment section will appear at the top of the comments to make adding comment easier.', 'wphb' ); ?></span>
						</div>
					</div><!-- End of tabs content holder -->

				</div>

			</div>
			<div id="lazy_load-limit" class="sui-margin-top" aria-label="<?php esc_html_e( 'Update comment count queried at once to reduce the query time.', 'wphb' ); ?>">
				<span class="sui-label-note" id="comments_per_page_label"><?php esc_html_e( 'Comment limit', 'wphb' ); ?></span>
				<?php
				$discussion_settings = esc_url( admin_url( 'options-discussion.php' ) );
				?>
				<span class="sui-description" id="comments_per_page_desc">
				<?php
					/* translators: %1$s - anchor link, %2$s - closing tag */
					printf( esc_html__( 'If you are using native WordPress comments, limiting the number of comments to grab from the database will reduce the time to query them. You can change the limit in %1$sDiscussion Settings%2$s.', 'wphb' ), '<a href="' . esc_url( $discussion_settings ) . '">', '</a>' );
				?>
				</span>
				<div class="sui-form-field">
					<input class="sui-form-control sui-input-sm" disabled value="<?php echo (int) get_option( 'comments_per_page' ); ?>" aria-labelledby="comments_per_page_label" aria-describedby="comments_per_page_desc">
				</div>
			</div>
			<div id="lazy_load-threshold" class="sui-margin-top" aria-label="<?php esc_html_e( 'Set comment threshold to lazy load comments.', 'wphb' ); ?>">
				<span class="sui-label-note" id="threshold_label">
					<?php esc_html_e( 'Threshold', 'wphb' ); ?>
				</span>
				<span class="sui-description" id="threshold_desc">
					<?php esc_html_e( 'In addition to choosing how many comments to lazy load each time, you can also prevent lazy loading if there are only a few comments. Set this value here as the minimum number of comments before engaging lazy loading.', 'wphb' ); ?>
				</span>
				<div class="sui-form-field">
					<input class="sui-form-control sui-input-sm" value="<?php echo esc_attr( $threshold ); ?>" name="threshold" aria-labelledby="threshold_label" aria-describedby="threshold_desc">
				</div>
			</div>
		</div>
		<?php if ( ! is_multisite() ) : ?>
			<div class="sui-upsell-row<?php echo $is_enabled ? '' : ' sui-hidden'; ?>" id="sui-upsell-gravtar-caching">
				<?php
					$gravatar_caching_url = add_query_arg(
						array(
							'view' => 'gravatar',
						),
						Utils::get_admin_menu_url( 'caching' )
					);
				?>
				<span class="sui-description">
					<?php
					/* translators: %1$s - anchor link, %2$s - closing tag */
					printf( esc_html__( 'Make sure you have activated %1$sGravatar Caching%2$s. It will store local copies of avatars used in comments and in your theme.', 'wphb' ), '<a href="' . esc_url( $gravatar_caching_url ) . '">', '</a>' );
					?>
				</span>
			</div>
		<?php endif; ?>
	</div>
</div>

<?php if ( ! $is_smush_installed || ! $is_smush_active || ! $smush_lazy_load ) : ?>
	<?php
	$smush_plugin_name           = Utils::is_member() ? 'Smush Pro' : 'Smush';
	$smush_installed_plugin_name = ( $is_smush_pro ) ? 'Smush Pro' : 'Smush';
	$can_install_plugin          = ! is_multisite() || is_network_admin();
	$show_smush_box              = false;
	$message                     = '';
	ob_start();
	?>
	<!-- Smush not installed -->
	<?php if ( $can_install_plugin && ! $is_smush_installed ) : ?>
		<?php $show_smush_box = true; ?>
		<a style="display: block; color: #17A8E3; margin-top: 10px;" href="<?php echo esc_url( Utils::get_link( 'smush' ) ); ?>" class="upsell-action-link" id="smush-install">
			<?php
			/* translators: %1$s - plugin name */
			printf( esc_html__( 'Install %s', 'wphb' ), esc_attr( $smush_plugin_name ) );
			?>
		</a>
	<!-- Smush is not active -->
	<?php elseif ( $can_install_plugin && ( $is_smush_installed && ! $is_smush_active ) ) : ?>
		<?php
		$show_smush_box = true;
		$activate_url   = ( $is_smush_pro ) ? $smush_activate_pro_url : $smush_activate_url;
		?>
		<a style="display: block; color: #17A8E3; margin-top: 10px;" href="<?php echo esc_url( $activate_url ); ?>" class="upsell-action-link" id="smush-activate">
			<?php
			/* translators: %s - Plugin name */
			printf( esc_html__( 'Activate %s', 'wphb' ), esc_attr( $smush_installed_plugin_name ) );
			?>
		</a>
	<?php elseif ( $is_smush_installed && $is_smush_active && $is_smush_lazy_load_configurable && ! $smush_lazy_load ) : ?>
		<?php $show_smush_box = true; ?>
		<a style="display: block; color: #17A8E3; margin-top: 10px;" href="<?php echo esc_url( $activate_smush_lazy_load_url ); ?>" class="upsell-action-link" id="smush-activate-lazy-load">
			<?php printf( esc_html__( 'Activate Lazy Load', 'wphb' ), esc_attr( $smush_plugin_name ) ); ?>
		</a>
	<?php endif; ?>
	<?php $message = ob_get_clean(); ?>
	<?php if ( $show_smush_box && ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
	<div class="sui-box-settings-row sui-upsell-row">
		<img class="sui-image sui-upsell-image" style="width: auto !important; height: 108px !important; margin-bottom: -30px" src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/smush-share-widget.png' ); ?>"
			srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/smush-share-widget.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/smush-share-widget@2x.png' ); ?> 2x" alt="">

		<div class="sui-upsell-notice">
			<p>
				<?php
					/* translators: %1$s - plugin name */
					printf( esc_html__( 'Did you know that %1$s provides media lazy loading? It will reduce load on your server and will speed up the page load time. %1$s also delivers up to 2x better compression.', 'wphb' ), esc_attr( $smush_plugin_name ) );
					echo '<br>';
					echo $message;
				?>
			</p>
		</div>
	</div>
	<?php endif; ?>
<?php endif; ?>
