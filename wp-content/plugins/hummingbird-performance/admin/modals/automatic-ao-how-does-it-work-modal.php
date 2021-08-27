<?php
/**
 * Automatic asset optimization 'How does it work' modal.
 *
 * @since 2.6.0
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="automatic-ao-hdiw-modal" class="sui-modal sui-modal-sm">
	<div
		role="dialog"
		id="automatic-ao-hdiw-modal-content"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="automatic-ao-hdiw-modal-title"
		aria-describedby="automatic-ao-hdiw-modal-desc"
	>
		<div class="sui-box">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--40">
				<span class="sui-button-icon sui-button-float--left sui-tooltip sui-tooltip-right" id="automatic-ao-hdiw-modal-expand" data-tooltip="<?php esc_attr_e( 'Expand', 'wphb' ); ?>">
					<span class="sui-icon-arrows-out sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Expand', 'wphb' ); ?></span>
				</span>
				<span class="sui-button-icon sui-button-float--left sui-tooltip sui-tooltip-right" id="automatic-ao-hdiw-modal-collapse" data-tooltip="<?php esc_attr_e( 'Collapse', 'wphb' ); ?>">
					<span class="sui-icon-arrows-in sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Collapse', 'wphb' ); ?></span>
				</span>

				<span class="sui-side-tabs sui-button-float--right" style="margin: -5px 40px 0 0">
					<div class="sui-tabs-menu">
						<label id="hdw-auto-trigger-label" for="hdw-auto-trigger" class="sui-tab-item active">
							<input type="radio" id="hdw-auto-trigger" checked="checked">
							<?php esc_html_e( 'Automatic', 'wphb' ); ?>
						</label>

						<label id="hdw-manual-trigger-label" for="hdw-manual-trigger" class="sui-tab-item">
							<input type="radio" id="hdw-manual-trigger">
							<?php esc_html_e( 'Manual', 'wphb' ); ?>
						</label>
					</div>
				</span>

				<span class="sui-button-icon sui-button-float--right" data-modal-close="" >
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog window', 'wphb' ); ?></span>
				</span>
			</div>

			<div class="sui-box-body">
				<h3 id="automatic-ao-hdiw-modal-title"><?php esc_html_e( 'How Does it Work?', 'wphb' ); ?></h3>
				<p class="sui-description" id="automatic-ao-hdiw-modal-desc">
					<?php
					esc_html_e( "This is a quick guide to help you configure Hummingbird's Asset Optimization.", 'wphb' );
					if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) {
						printf( /* translators: %1$s - space, %2$s - link */
							__( '%1$sFor more detailed information, please check out our in-depth <a href="%2$s" target="_blank" >documentation</a>.', 'wphb' ),
							'&nbsp;',
							'https://wpmudev.com/docs/wpmu-dev-plugins/hummingbird/#asset-optimization'
						);
					}
					?>
				</p>
			</div>

			<div class="sui-accordion sui-accordion-flushed">
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'How Does Automatic Optimization Work?', 'wphb' ); ?>
						</div>
						<div class="sui-accordion-col-auto">
							<span class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_attr_e( 'Expand item', 'wphb' ); ?>">
								<span class="sui-icon-chevron-down" aria-hidden="true"></span>
							</span>
						</div>
					</div>
					<div class="sui-accordion-item-body">
						<div class="sui-box">
							<div class="sui-box-body">
								<p><?php esc_html_e( 'We developed automatic optimization so you can spend less time on configuration, while still seeing the same positive results that can be achieved through manual optimization.', 'wphb' ); ?></p>
								<p><?php esc_html_e( "So what exactly does each automated feature do behind the scenes? Let's find out.", 'wphb' ); ?></p>

								<ol style="list-style-type: none;">
									<li>
										<h4>
											<span class="sui-icon-hummingbird" aria-hidden="true" style="margin-left: -20px;"></span>
											<?php esc_html_e( 'Speedy', 'wphb' ); ?>
										</h4>
										<?php esc_html_e( 'Speedy optimization is a higher level of optimization, as it not only compresses your files but it also "auto-combines" smaller files together (*only when two or more files have identical attributes), which helps to reduce the number of requests made when a page is loaded.', 'wphb' ); ?>
									</li>
									<li>
										<h4>
											<span class="sui-icon-speed-optimize" aria-hidden="true" style="margin-left: -20px;"></span>
											<?php esc_html_e( 'Basic', 'wphb' ); ?>
										</h4>
										<?php esc_html_e( 'When Basic optimization is enabled, Hummingbird automatically compresses all of your unoptimized files, generating a newer and faster version of each. It also removes clutter from CSS and JavaScript files, helping to improve your site speed even further.', 'wphb' ); ?>
									</li>
								</ol>
							</div>
						</div>
					</div>
				</div><!-- /.sui-accordion-item -->
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'How to Configure Automatic Optimization', 'wphb' ); ?>
						</div>
						<div class="sui-accordion-col-auto">
							<span class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_attr_e( 'Expand item', 'wphb' ); ?>">
								<span class="sui-icon-chevron-down" aria-hidden="true"></span>
							</span>
						</div>
					</div>
					<div class="sui-accordion-item-body">
						<div class="sui-box">
							<div class="sui-box-body">
								<p><?php esc_html_e( "The answer is as simple as enabling one of the given optimization options. From this point your files (CSS and JS) will be queued for optimization, meaning after someone visits your homepage, they'll be optimized. For better results, follow these steps:", 'wphb' ); ?></p>
								<ol style="list-style-type: none;">
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 1: Disable Caching Systems', 'wphb' ); ?></strong><br>
										<?php esc_html_e( "Before configuring Asset Optimization, caching systems should be disabled completely to prevent further issues. It's important to check that all caching systems (including server-side caching) are not active in the background as well.", 'wphb' ); ?>
									</li>
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 2: Wait For Files To Optimize', 'wphb' ); ?></strong><br>
										<?php esc_html_e( "After you enable the feature, files will be queued for optimization. This means they aren't optimized immediately, they will be optimized once someone visits your homepage.", 'wphb' ); ?>
									</li>
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 3: Turn Caching Systems Back On', 'wphb' ); ?></strong><br>
										<?php esc_html_e( 'After you received visits on your site, you can now re-enable your caching systems.', 'wphb' ); ?>
									</li>
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 4: Log Out From wp-admin and Check Front-end', 'wphb' ); ?></strong><br>
										<?php esc_html_e( 'Next, log out from wp-admin and check the front-end of your website to make sure no issues occurred. Switch to your browsers built-in inspector, and navigate to the Network tab in your site console.', 'wphb' ); ?>
									</li>
								</ol>
							</div>
						</div>
					</div>
				</div><!-- /.sui-accordion-item -->
			</div><!-- /.sui-accordion -->

			<div class="sui-box-body sui-no-padding-bottom">
				<h4><?php esc_html_e( 'Frequently Asked Questions', 'wphb' ); ?></h4>
			</div>

			<div class="sui-accordion sui-accordion-flushed">
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'How Will Auto-optimization Handle Newly Added Files?', 'wphb' ); ?>
						</div>
						<div class="sui-accordion-col-auto">
							<span class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_attr_e( 'Expand item', 'wphb' ); ?>">
								<span class="sui-icon-chevron-down" aria-hidden="true"></span>
							</span>
						</div>
					</div>
					<div class="sui-accordion-item-body">
						<div class="sui-box">
							<div class="sui-box-body">
								<p><?php esc_html_e( 'Hummingbird will auto-detect newly added plugin and theme files and optimize them for you. However, to avoid conflicts and issues, Hummingbird won’t remove any old files which were removed from a plugin or theme. That’s why we recommend file scanning once in a while to keep everything in sync.', 'wphb' ); ?></p>
							</div>
						</div>
					</div>
				</div><!-- /.sui-accordion-item -->
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'How Do I Configure My Site Without Breaking it?', 'wphb' ); ?>
						</div>
						<div class="sui-accordion-col-auto">
							<span class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_attr_e( 'Expand item', 'wphb' ); ?>">
								<span class="sui-icon-chevron-down" aria-hidden="true"></span>
							</span>
						</div>
					</div>
					<div class="sui-accordion-item-body">
						<div class="sui-box">
							<div class="sui-box-body">
								<p><?php esc_html_e( 'Before configuring Asset Optimization, try to disable page caching completely to prevent further issues. Make sure server-side caching is not active in the background as well. Once the preset status has changed from Queued to Optimized, you can then enable the page caching. After enabling page caching, log out from wp-admin and verify that the page is served, cached, and working without issues.', 'wphb' ); ?></p>
							</div>
						</div>
					</div>
				</div><!-- /.sui-accordion-item -->
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'What Happens if My Site Breaks?', 'wphb' ); ?>
						</div>
						<div class="sui-accordion-col-auto">
							<span class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_attr_e( 'Expand item', 'wphb' ); ?>">
								<span class="sui-icon-chevron-down" aria-hidden="true"></span>
							</span>
						</div>
					</div>
					<div class="sui-accordion-item-body">
						<div class="sui-box">
							<div class="sui-box-body">
								<p><?php esc_html_e( "The easiest way to fix your broken site is to disable Automatic Optimization. Once this is done all the changes will be reverted back. After disabling the feature, you also need to clear the browser cache, so you can see the changes. After disabling the feature and clearing the browser cache, verify if the page is no longer showing the compressed version of the page by checking the network tab in your console. Optimized files, depending on your settings, will either be served from our blazingly fast CDN or a local Hummingbird directory (by default - /wp-content/uploads/hummingbird-assets/), and the file name will be hashed. Note that Hummingbird also won't modify your original files… at all!", 'wphb' ); ?></p>
							</div>
						</div>
					</div>
				</div><!-- /.sui-accordion-item -->
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'How Do I Know When Files Are Optimized?', 'wphb' ); ?>
						</div>
						<div class="sui-accordion-col-auto">
							<span class="sui-button-icon sui-accordion-open-indicator" aria-label="<?php esc_attr_e( 'Expand item', 'wphb' ); ?>">
								<span class="sui-icon-chevron-down" aria-hidden="true"></span>
							</span>
						</div>
					</div>
					<div class="sui-accordion-item-body">
						<div class="sui-box">
							<div class="sui-box-body">
								<p><?php esc_html_e( "After enabling Automatic Optimization, files will be queued and when someone visits your site, they'll be optimized. To check if the page is optimized, you can use the inspect mode and navigate to the network tab in your console. As mentioned above, optimized files will either be served from our CDN or a local Hummingbird directory, and the file name will be hashed.", 'wphb' ); ?></p>
							</div>
						</div>
					</div>
				</div><!-- /.sui-accordion-item -->
			</div><!-- /.sui-accordion -->

			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<div class="sui-box-body">
					<h4><?php esc_html_e( 'Related Articles', 'wphb' ); ?></h4>

					<p class="sui-description">
						<a href="https://wpmudev.com/docs/wpmu-dev-setting-configurations/wpmu-dev-performance-optimization-guide/" target="_blank">
							<?php esc_html_e( 'WPMU DEV Performance Optimization Guide', 'wphb' ); ?>
						</a><br>
						<a href="https://wpmudev.com/docs/wpmu-dev-plugins/hummingbird/#performance-test" target="_blank">
							<?php esc_html_e( 'How to Measure Page Speed', 'wphb' ); ?>
						</a><br>
						<a href="https://wpmudev.com/docs/wpmu-dev-plugins/hummingbird/#caching" target="_blank">
							<?php esc_html_e( 'Everything You Need To Know About Caching', 'wphb' ); ?>
						</a>
					</p>

					<p class="sui-description" style="margin-top: 50px">
						<?php
						printf( /* translators: %s - link */
							__( 'Didn\'t find the answer you were looking for? Check out our detailed <a href="%s" target="_blank" >documentation</a> or contact our support team for further assistance.', 'wphb' ),
							'https://wpmudev.com/docs/wpmu-dev-plugins/hummingbird/#asset-optimization'
						);
						?>
					</p>

					<p>
						<a role="button" class="sui-button sui-margin-bottom" target="_blank" href="<?php echo esc_url( \Hummingbird\Core\Utils::get_link( 'support' ) ); ?>">
							<?php esc_html_e( 'CONTACT SUPPORT', 'wphb' ); ?>
						</a>
					</p>
				</div><!-- /.sui-box-body -->
			<?php endif; ?>
		</div><!-- /.sui-box -->
	</div>
</div>
