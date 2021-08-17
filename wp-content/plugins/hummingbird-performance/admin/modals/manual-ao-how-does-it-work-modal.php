<?php
/**
 * Manual asset optimization 'How does it work' modal.
 *
 * @since 2.6.0
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div id="manual-ao-hdiw-modal" class="sui-modal sui-modal-sm">
	<div
		role="dialog"
		id="manual-ao-hdiw-modal-content"
		class="sui-modal-content"
		aria-modal="true"
		aria-labelledby="manual-ao-hdiw-modal-title"
		aria-describedby="manual-ao-hdiw-modal-desc"
	>
		<div class="sui-box" id="manual-ao-hdiw-modal-header-wrap">
			<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60 ">
				<span class="sui-button-icon sui-button-float--left sui-tooltip sui-tooltip-right" id="manual-ao-hdiw-modal-expand" data-tooltip="<?php esc_attr_e( 'Expand', 'wphb' ); ?>">
					<span class="sui-icon-arrows-out sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Expand', 'wphb' ); ?></span>
				</span>
				<span class="sui-button-icon sui-button-float--left sui-tooltip sui-tooltip-right" id="manual-ao-hdiw-modal-collapse" data-tooltip="<?php esc_attr_e( 'Collapse', 'wphb' ); ?>">
					<span class="sui-icon-arrows-in sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Collapse', 'wphb' ); ?></span>
				</span>

				<span class="sui-side-tabs sui-button-float--right" style="margin: -5px 40px 0 0">
					<div class="sui-tabs-menu">
						<label id="hdw-auto-trigger-label" for="hdw-auto-trigger" class="sui-tab-item">
							<input type="radio" id="hdw-auto-trigger">
							<?php esc_html_e( 'Automatic', 'wphb' ); ?>
						</label>

						<label id="hdw-manual-trigger-label" for="hdw-manual-trigger" class="sui-tab-item active">
							<input type="radio" id="hdw-manual-trigger" checked="checked">
							<?php esc_html_e( 'Manual', 'wphb' ); ?>
						</label>
					</div>
				</span>

				<span class="sui-button-icon sui-button-float--right" id="manual-ao-hdiw-modal-close-btn" data-modal-close="" >
					<span class="sui-icon-close sui-md" aria-hidden="true"></span>
					<span class="sui-screen-reader-text"><?php esc_html_e( 'Close this dialog window', 'wphb' ); ?></span>
				</span>
			</div>

			<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
				<div class="sui-box-body sui-spacing-bottom--0">
					<script src="//fast.wistia.com/embed/medias/q9hba9mu96.jsonp" async></script>
					<script src="//fast.wistia.com/assets/external/E-v1.js" async></script>
					<script>
						window._wq = window._wq || [];
						_wq.push( { id: 'q9hba9mu96', onReady: function(video) {
							var vp = false;
							video.bind( "play", function() {
								if( !vp ) {
									var mod = document.getElementById( 'manual-ao-hdiw-modal' );
									var el 	= document.getElementById( 'manual-ao-hdiw-modal-header-wrap' );
									el.classList.add( 'video-playing' );
									if( mod.classList.contains( 'sui-modal-sm' ) ) {
										el.classList.add( 'sui-box-sticky' );
									}
								}
								vp = true;
							} );
						} } );
					</script>
					<div class="wistia_responsive_padding" style="padding:56.25% 0 0 0;position:relative;">
						<div class="wistia_responsive_wrapper" style="height:100%;left:0;position:absolute;top:0;width:100%;">
							<div class="wistia_embed wistia_async_q9hba9mu96 seo=false videoFoam=true" style="height:100%;width:100%">&nbsp;</div>
						</div>
					</div>
					<div id="manual-ao-hdiw-modal-video-desc" class="sui-description"><?php esc_html_e( 'Check this short video about how to configure asset optimization.', 'wphb' ); ?></div>
				</div>
			<?php endif; ?>
		</div>
		<div class="sui-box" id="manual-ao-hdiw-modal-body-wrap">
			<div class="sui-box-body">
				<h3 id="manual-ao-hdiw-modal-title"><?php esc_html_e( 'How Does it Work?', 'wphb' ); ?></h3>
				<p class="sui-description" id="manual-ao-hdiw-modal-desc">
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
				<p class="sui-description">
					<?php esc_html_e( "Hummingbird's automatic optimization is a great tool for improving page speed, but sometimes for advanced configurations, you need to configure optimization settings manually.", 'wphb' ); ?>
				</p>
			</div>

			<div class="sui-accordion sui-accordion-flushed">
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'How We Approach Advanced Asset Optimization', 'wphb' ); ?>
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
								<p><?php esc_html_e( "Below is an example workflow of how we approach advanced asset optimization. In most cases, following these steps should suffice, however, every site is different and yours might require another approach. If this is the case, don't hesitate to get in touch with our support team, who are well equipped to help with a variety of scenarios.", 'wphb' ); ?></p>
								<h4><?php esc_html_e( 'Before getting started, we recommend completing the following checklist', 'wphb' ); ?>:</h4>
								<ol style="list-style-type: disc;">
									<li><?php esc_html_e( 'Test changes on your staging environment before moving to production. Keep in mind staging and production will not always match. Especially on sites that require premium licenses and domain-specific configurations.', 'wphb' ); ?></li>
									<li><?php esc_html_e( "Before configuring Asset Optimization, disable caching systems completely to prevent further issues. The feature automatically disables page caching, however, it's also important to check that other caching systems (e.g. server-side caching) are not active in the background.", 'wphb' ); ?></li>
									<li><?php esc_html_e( "Configure your theme and plugins first. Hummingbird's Asset Optimization should be the last thing you configure.", 'wphb' ); ?></li>
									<li><?php esc_html_e( "Make changes one at a time, and verify that each was successful before moving to the next. If you're confident in what you're doing you can also make bulk changes to assets if you choose.", 'wphb' ); ?></li>
								</ol>
							</div>
						</div>
					</div>
				</div><!-- /.sui-accordion-item -->
				<div class="sui-accordion-item">
					<div class="sui-accordion-item-header">
						<div class="sui-accordion-item-title">
							<?php esc_html_e( 'Configuration Steps', 'wphb' ); ?>
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
								<ol style="list-style-type: none;">
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 1: Run Asset Optimization Scan', 'wphb' ); ?></strong><br>
										<?php esc_html_e( "The first step is running Hummingbird's Asset Optimization scan. Note, in some cases when styles/scripts are not properly enqueued by themes/plugins, you might notice issues on the front-end. However, these will be resolved during the upcoming steps.", 'wphb' ); ?>
									</li>
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 2: Bulk Compress And Combine', 'wphb' ); ?></strong><br>
										<?php esc_html_e( "Next it's time to bulk compress and combine. This step is as simple as selecting the CSS and JS checkboxes above each section, and applying the compress and combine changes in the bulk updating modal. After making the changes and pushing them live, check your site to ensure everything is working as it should. Also, check your console for any JS errors. If you do come across issues, decompress and separate any of the files causing errors - then save and check again.", 'wphb' ); ?>
									</li>
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 3: Move All Files To The Footer', 'wphb' ); ?></strong><br>
										<?php esc_html_e( 'Move all asset files to load from the footer, except jQuery, jQuery Migrate, and core theme JavaScript files. If you\'re unsure what files are from your theme - use the "Filter theme and plugin sort" tool. After you\'re done, click "Publish Changes" to push your changes live.', 'wphb' ); ?>
									</li>
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 4: Defer Some Of The Scripts', 'wphb' ); ?></strong><br>
										<?php
										printf(
											__( 'The next step is deferring the loading of scripts that are not needed to run immediately after a page has loaded (like a form processing or spam protection scripts). Use the "Force load this file after the page is loaded" button %s, which can be found in the "JavaScripts" section.', 'wphb' ),
											'<span class="sui-icon-defer" aria-hidden="true" style="padding-left:3px;padding-right:3px;border:1px solid #bbb;"></span>'
										);
										?>
									</li>
									<li>
										<span class="sui-icon-check" aria-hidden="true" style="margin-left: -20px;"></span>
										<strong><?php esc_html_e( 'Step 5: Remove Unused CSS', 'wphb' ); ?></strong><br>
										<?php esc_html_e( 'Before using Hummingbird to remove unused CSS, make sure to disable and/or remove the plugins that load unused CSS. To identify these plugins, check "Code coverage" in Google Chrome dev tools. Check the stylesheet URL to identify the plugin or theme responsible for that CSS file. Look for the plugins that have a lot of stylesheets in the list with a lot of red in code coverage.', 'wphb' ); ?>
									</li>
								<ol>
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
							<?php esc_html_e( 'How Do I Know When My Files Are Optimized?', 'wphb' ); ?>
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
								<p><?php esc_html_e( 'After enabling Automatic Optimization, files will be queued and when someone visits your site optimization will be automatically triggered via cron. To check if the page is optimized, you can use inspect mode and navigate to the network tab in your console. Optimized files, depending on your settings, will either be served from our blazingly fast CDN or a local Hummingbird directory (by default - /wp-content/uploads/hummingbird-assets/), and the file name will be hashed.', 'wphb' ); ?></p>
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
								<p><?php esc_html_e( "If a more serious issue should occur, a broken site can easily be fixed by disabling Automatic Optimization. Once this is done all the changes you made will be reverted back. After disabling the feature, it's also important to clear your browser and page cache, so you can see the changes. Next you'll simply need to verify the page is no longer showing the compressed version by checking the network tab in your console. As mentioned above, optimized files will either be served from our CDN or a local Hummingbird directory, and the file name will be hashed. Finally, note that Hummingbird also won't modify your original files… at all!", 'wphb' ); ?></p>
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
						printf(
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
