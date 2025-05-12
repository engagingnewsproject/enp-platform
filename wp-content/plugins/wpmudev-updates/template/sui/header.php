<div class="sui-header" id="wpmudev-dashboard-header">
	<h1 class="sui-header-title">
		<?php echo esc_html( $page_title ); ?>
	</h1>
	<div class="sui-actions-right">
		<div class="dashui-login-bar">
			<?php if ( WPMUDEV_CUSTOM_API_SERVER ) : ?>
				<div
					class="sui-tooltip sui-tooltip-bottom-right sui-tooltip-bottom-right-mobile"
					data-tooltip="<?php echo esc_attr( sprintf( "Custom API Server:\n%s", WPMUDEV_CUSTOM_API_SERVER ) ); ?>"
				>
					<i class="sui-icon-plug-connected"></i>
				</div>
			<?php endif; ?>

			<?php if ( $is_logged_in ) : ?>
				<a
					href="<?php echo esc_url( $url_dash ); ?>"
					class="sui-button-icon sui-tooltip sui-tooltip-bottom sui-tooltip-bottom-left-mobile"
					target="_blank"
					data-tooltip="<?php esc_html_e( 'Hub', 'wpmudev' ); ?>"
				>
					<i class="sui-icon-hub sui-md" aria-hidden="true"></i>
				</a>

				<a
					href="<?php echo esc_url( $documentation_url ); ?>"
					target="_blank"
					class="sui-button-icon sui-tooltip sui-tooltip-bottom sui-tooltip-bottom-left-mobile"
					data-tooltip="<?php esc_html_e( 'Documentation', 'wpmudev' ); ?>"
				>
					<i class="sui-icon-academy sui-md" aria-hidden="true"></i>
				</a>

				<?php if ( WPMUDEV_Dashboard::$utils->can_access_feature( 'support' ) || $has_hosted_access ) : ?>
					<a
						href="<?php echo esc_url( $url_support ); ?>"
						class="sui-button-icon sui-tooltip sui-tooltip-bottom sui-tooltip-bottom-left-mobile"
						data-tooltip="<?php esc_html_e( 'Support', 'wpmudev' ); ?>"
					>
						<i class="sui-icon-help-support sui-md" aria-hidden="true"></i>
					</a>
				<?php endif; ?>

				<div class="sui-dropdown">
					<?php if ( ! empty( $profile['avatar'] ) ) : ?>
						<button class="dashui-logout-button sui-dropdown-anchor">
							<img
								alt=""
								src="<?php echo esc_url( $profile['avatar'] ); ?>"
								aria-hidden="true"
							/>
							<i class="sui-icon-chevron-down" aria-hidden="true"></i>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Open settings', 'wpmudev' ); ?></span>
						</button>
					<?php else : ?>
						<button class="sui-button-icon sui-dropdown-anchor">
							<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
							<span class="sui-screen-reader-text"><?php esc_html_e( 'Open settings', 'wpmudev' ); ?></span>
						</button>
					<?php endif; ?>
					<ul>
						<li>
							<?php if ( $free_services_active ) : ?>
								<button data-modal-open="logout-confirmation-content">
									<i class="sui-icon-plug-disconnected" aria-hidden="true"></i> <?php esc_html_e( 'Logout', 'wpmudev' ); ?>
								</button>
							<?php else : ?>
								<a href="<?php echo esc_url( $url_logout ); ?>">
									<i class="sui-icon-plug-disconnected" aria-hidden="true"></i> <?php esc_html_e( 'Logout', 'wpmudev' ); ?>
								</a>
							<?php endif; ?>
						</li>
					</ul>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
<?php if ( $is_logged_in && $free_services_active ) : ?>
	<?php $this->render( 'sui/popup-logout-confirmation' ); ?>
<?php endif; ?>