<?php
/**
 * Integrations Cloudflare connect modal.
 *
 * @since 3.0.0
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-modal sui-modal-md">
	<div
		role="dialog"
		id="cloudflare-connect"
		class="sui-modal-content"
		aria-live="polite"
		aria-modal="true"
		aria-labelledby="cloudflare-connect-title"
		aria-describedby="cloudflare-connect-desc"
	>
		<div id="slide-cloudflare-connect" class="sui-modal-slide sui-loaded sui-active">
			<div class="sui-box sui-padding-bottom">
				<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
					<figure class="sui-box-logo" aria-hidden="true">
						<img src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/integrations/icon-cloudflare-large.png' ); ?>" alt="<?php esc_attr_e( 'Connect Cloudflare', 'wphb' ); ?>"
							srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/integrations/icon-cloudflare-large.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/integrations/icon-cloudflare-large@2x.png' ); ?> 2x">
					</figure>

					<button class="sui-button-icon sui-button-float--right" data-modal-close="">
						<span class="sui-icon-close sui-md" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this modal', 'wphb' ); ?></span>
					</button>

					<h3 id="cloudflare-connect-title" class="sui-box-title sui-lg">
						<?php esc_html_e( 'Connect Cloudflare', 'wphb' ); ?>
					</h3>

					<p id="cloudflare-connect-desc" class="sui-description">
						<?php
						printf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
							esc_html__( 'Connect to your Cloudflare account with either a Global API key or an API token and configure your settings in Hummingbird. Need help getting your API credentials? %1$sFollow the steps%2$s below.', 'wphb' ),
							'<a href="#" id="cloudflare-connect-steps">',
							'</a>'
						)
						?>
					</p>
				</div>

				<div class="sui-side-tabs" style="margin-top: 20px;">
					<div class="sui-tabs-menu" style="justify-content: center">
						<label for="cf-api-key-tab" class="sui-tab-item active">
							<input type="radio" name="cf_connection_type" value="key" id="cf-api-key-tab" data-tab-menu="cf-api-key" checked="checked">
							<?php esc_html_e( 'Global API Key', 'wphb' ); ?>
						</label>
						<label for="cf-token-tab" class="sui-tab-item">
							<input type="radio" name="cf_connection_type" value="token" id="cf-token-tab" data-tab-menu="cf-token">
							<?php esc_html_e( 'API Token', 'wphb' ); ?>
						</label>
					</div>
					<form id="cloudflare-credentials" class="sui-tabs-content">
						<div class="sui-tab-content sui-tab-boxed sui-no-margin sui-no-padding active" style="border: 0" data-tab-content="cf-api-key">
							<div class="sui-box-body sui-no-padding-bottom">
								<div class="sui-form-field">
									<label for="cloudflare-email" id="label-cloudflare-email" class="sui-label">
										<?php esc_html_e( 'Cloudflare account email', 'wphb' ); ?>
									</label>
									<input type="email"
										placeholder="<?php esc_attr_e( 'Enter email address here', 'wphb' ); ?>"
										id="cloudflare-email"
										name="cloudflare-email"
										class="sui-form-control"
										aria-labelledby="label-cloudflare-email"
									/>
								</div>

								<div class="sui-form-field" id="api-key-form-field">
									<label for="cloudflare-api-key" id="label-cloudflare-api-key" class="sui-label">
										<?php esc_html_e( 'Cloudflare Global API key', 'wphb' ); ?>
									</label>
									<div class="sui-with-button sui-with-button-inside">
										<input type="password"
											placeholder="<?php esc_attr_e( 'Enter 37 digit API key here', 'wphb' ); ?>"
											id="cloudflare-api-key"
											name="cloudflare-api-key"
											class="sui-form-control"
											autocomplete="off"
											aria-labelledby="error-api-key label-cloudflare-api-key"
										/>
										<span id="error-api-key" class="sui-error-message" style="display: none;" role="alert"></span>
										<button class="sui-button-icon" type="button">
											<span aria-hidden="true" class="sui-icon-eye"></span>
											<span class="sui-password-text sui-screen-reader-text">
												<?php esc_html_e( 'Show API key', 'wphb' ); ?>
											</span>
											<span class="sui-password-text sui-screen-reader-text sui-hidden">
												<?php esc_html_e( 'Hide API key', 'wphb' ); ?>
											</span>
										</button>
									</div>
								</div>
							</div>
						</div>
						<div class="sui-tab-content sui-tab-boxed sui-no-margin sui-no-padding" style="border: 0" data-tab-content="cf-token">
							<div class="sui-box-body sui-no-padding-bottom">
								<div class="sui-form-field" id="api-token-form-field">
									<label for="cloudflare-api-token" id="label-cloudflare-api-token" class="sui-label">
										<?php esc_html_e( 'Cloudflare API token', 'wphb' ); ?>
									</label>
									<div class="sui-with-button sui-with-button-inside">
										<input type="password"
											minlength="1"
											placeholder="<?php esc_attr_e( 'Enter 40 digit API token here', 'wphb' ); ?>"
											id="cloudflare-api-token"
											name="cloudflare-api-token"
											class="sui-form-control"
											autocomplete="off"
											aria-labelledby="error-api-token label-cloudflare-api-token"
										/>
										<span id="error-api-token" class="sui-error-message" style="display: none;" role="alert"></span>
										<button class="sui-button-icon" type="button">
											<span aria-hidden="true" class="sui-icon-eye"></span>
											<span class="sui-password-text sui-screen-reader-text">
												<?php esc_html_e( 'Show API token', 'wphb' ); ?>
											</span>
											<span class="sui-password-text sui-screen-reader-text sui-hidden">
												<?php esc_html_e( 'Hide API token', 'wphb' ); ?>
											</span>
										</button>
									</div>
								</div>
							</div>
						</div>
					</form>
				</div>

				<div class="sui-box-footer sui-flatten sui-content-separated sui-no-padding-bottom">
					<small class="sui-no-margin-bottom">
						<a href="#" class="cloudflare-connect-steps" id="cloudflare-show-key-help">
							<span><?php esc_html_e( 'Need help getting your Global API key?', 'wphb' ); ?></span>
							<span class="sui-icon-chevron-down" aria-hidden="true"></span>
						</a>
					</small>

					<button class="sui-button sui-button-blue" id="cloudflare-connect-save" aria-live="polite" form="cloudflare-credentials" disabled="disabled">
						<span class="sui-button-text-default" aria-hidden="true">
							<?php esc_html_e( 'Connect', 'wphb' ); ?>
						</span>
						<span class="sui-button-text-onload">
						<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							<?php esc_html_e( 'Connecting...', 'wphb' ); ?>
						</span>
					</button>
				</div>

				<ol id="cloudflare-key-how-to" class="sui-border-frame sui-hidden">
					<li>
						<?php
						printf( /* translators: %1$s - <a>, %2$s - </a> */
							esc_html__( '%1$sLog in%2$s to your Cloudflare account.', 'wphb' ),
							'<a target="_blank" href="https://dash.cloudflare.com/login">',
							'</a>'
						);
						?>
					</li>
					<li><?php esc_html_e( 'Go to My Profile.', 'wphb' ); ?></li>
					<li><?php esc_html_e( 'Switch to API Tokens tab.', 'wphb' ); ?></li>
					<li>
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( "Click '%1\$sView%2\$s' button and copy the Global API Key identifier.", 'wphb' ),
							'<strong>',
							'</strong>'
						)
						?>
					</li>
				</ol>
				<ol id="cloudflare-token-how-to" class="sui-border-frame sui-hidden">
					<li>
						<?php
						printf( /* translators: %1$s - <a>, %2$s - </a> */
							esc_html__( '%1$sLog in%2$s to your Cloudflare account.', 'wphb' ),
							'<a target="_blank" href="https://dash.cloudflare.com/login">',
							'</a>'
						);
						?>
					</li>
					<li><?php esc_html_e( 'Go to My Profile.', 'wphb' ); ?></li>
					<li>
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( "Switch to the API Tokens tab, and click '%1\$sCreate Token%2\$s'.", 'wphb' ),
							'<strong>',
							'</strong>'
						)
						?>
					</li>
					<li>
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( "Find the WordPress row and click '%1\$sUse Template%2\$s'.", 'wphb' ),
							'<strong>',
							'</strong>'
						)
						?>
					</li>
					<li>
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( "Under Zone Resources, first select '%1\$sSpecific Zone%2\$s' from the dropdown menu and then select this website's domain name.", 'wphb' ),
							'<strong>',
							'</strong>'
						)
						?>
					</li>
					<li>
						<?php
						printf( /* translators: %1$s - opening <strong> tag, %2$s - closing </strong> tag */
							esc_html__( "Click '%1\$sContinue to summary%2\$s', and then click '%1\$sCreate Token%2\$s'.", 'wphb' ),
							'<strong>',
							'</strong>'
						)
						?>
					</li>
					<li><?php esc_html_e( 'Copy the WordPress API token and paste it into the Cloudflare API token field in Hummingbird.', 'wphb' ); ?></li>
				</ol>
			</div>
		</div>

		<div id="slide-cloudflare-zones" class="sui-modal-slide">
			<div class="sui-box">
				<div class="sui-box-header sui-flatten sui-content-center sui-spacing-top--60">
					<figure class="sui-box-logo" aria-hidden="true">
						<img src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/integrations/icon-cloudflare-large.png' ); ?>" alt="<?php esc_attr_e( 'Connect Cloudflare', 'wphb' ); ?>"
							srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/integrations/icon-cloudflare-large.png' ); ?> 1x, <?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/integrations/icon-cloudflare-large@2x.png' ); ?> 2x">
					</figure>

					<button class="sui-button-icon sui-button-float--right" onclick="window.location.reload()">
						<span class="sui-icon-close sui-md" aria-hidden="true"></span>
						<span class="sui-screen-reader-text"><?php esc_attr_e( 'Close this modal', 'wphb' ); ?></span>
					</button>

					<h3 class="sui-box-title sui-lg">
						<?php esc_html_e( 'Connect Cloudflare', 'wphb' ); ?>
					</h3>

					<p class="sui-description">
						<?php esc_html_e( 'Select the zone that matches your domain name.', 'wphb' ); ?>
					</p>
				</div>

				<div class="sui-box-body">
					<div class="sui-form-field">
						<label class="sui-label">
							<?php esc_html_e( 'Select Zone', 'wphb' ); ?>
						</label>
						<select id="cloudflare-zones" class="sui-select" data-placeholder="<?php esc_attr_e( 'Select zone', 'wphb' ); ?>">
							<option></option>
						</select>
					</div>

					<div class="sui-notice sui-notice-warning">
						<div class="sui-notice-content">
							<div class="sui-notice-message">
								<span class="sui-notice-icon sui-icon-info sui-md" aria-hidden="true"></span>
								<p>
									<?php esc_html_e( 'Cloudflare is connected, but it appears you don’t have any active zones for this domain. Double check your domain has been added to Cloudflare and tap re-check when ready or select one of the zones from the dropdown list below.', 'wphb' ); ?>
								</p>
							</div>
						</div>
					</div>
				</div>

				<div class="sui-box-footer sui-flatten sui-content-separated">
					<button class="sui-button sui-button-ghost" id="cf-recheck-zones" aria-live="polite">
						<span class="sui-button-text-default" aria-hidden="true">
							<span class="sui-icon-update" aria-hidden="true"></span>
							<?php esc_html_e( 'Re-check', 'wphb' ); ?>
						</span>
						<span class="sui-button-text-onload">
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							<?php esc_html_e( 'Re-checking...', 'wphb' ); ?>
						</span>
					</button>
					<button class="sui-button sui-button-blue" id="cloudflare-zone-save" aria-live="polite">
						<span class="sui-button-text-default" aria-hidden="true">
							<?php esc_html_e( 'Enable Cloudflare', 'wphb' ); ?>
						</span>
						<span class="sui-button-text-onload">
							<span class="sui-icon-loader sui-loading" aria-hidden="true"></span>
							<?php esc_html_e( 'Enabling...', 'wphb' ); ?>
						</span>
					</button>
				</div>
			</div>
		</div>
	</div>
</div>
