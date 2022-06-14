<div class="sui-box">

	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<i class="sui-icon-wrench-tool" aria-hidden="true"></i>
			<?php esc_html_e( 'Tools', 'wpmudev' ); ?>
		</h2>
		<?php if ( 'free' === $membership_data['membership'] ): ?>
			<div class="sui-actions-left">
				<span class="sui-tag sui-tag-pro">
					<?php echo __( 'Pro', 'wpmudev' ); ?>
				</span>
			</div>
		<?php endif; ?>
	</div>

	<div class="sui-box-body">
		<p><?php esc_html_e( 'Enable basic analytics for your client’s Dashboard and remove branding from WPMU DEV plugins.', 'wpmudev' ); ?></p>
	</div>

	<table class="sui-table dashui-table-tools">
		<tbody>
		<tr>
			<td class="dashui-item-content">
				<h4><?php esc_html_e( 'Analytics', 'wpmudev' ); ?></h4>
				<?php if ( ! $analytics_enabled ) : ?>
					<span class="sui-description" style="line-height: 22px;"><?php esc_html_e( "Add basic analytics tracking that doesn't require any third party integration, and display the data in the WordPress Admin Dashboard area.", 'wpmudev' ); ?></span>
					<?php if ( 'free' !== $membership_data['membership'] ): ?>
						<a href="<?php echo esc_url( $urls->analytics_url ); ?>" class="sui-button sui-button-blue" style="margin: 10px 0;">
							<?php esc_html_e( 'ACTIVATE', 'wpmudev' ); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>
			</td>
			<?php if ( $analytics_enabled ): ?>
				<td style="flex:0.7">
						<span class="sui-description">
							<?php printf( _nx( '%s Visit', '%s Visits', absint( $total_visits['value'] ), 'total visits', 'wpmudev' ), number_format_i18n( absint( $total_visits['value'] ) ) ); //phpcs:ignore ?>
						</span>
				</td>

				<td>
					<a class="sui-button-icon" href="<?php echo esc_url( admin_url( '/' ) ); ?>">
						<i class="sui-icon-graph-line" aria-hidden="true"></i>
					</a>
				</td>

				<td>
					<a class="sui-button-icon" href="<?php echo esc_url( $urls->analytics_url ); ?>">
						<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
					</a>
				</td>
			<?php endif; ?>
		</tr>

		<tr>
			<td class="dashui-item-content">
				<h4><?php esc_html_e( 'White Label', 'wpmudev' ); ?></h4>
				<?php if ( ! $whitelabel_settings['enabled'] ) : ?>
					<span class="sui-description" style="line-height: 22px;"><?php esc_html_e( "Remove WPMU DEV branding from all our plugins and replace it with your own branding for your clients.", 'wpmudev' ); ?></span>
					<?php if ( 'free' !== $membership_data['membership'] ): ?>
						<a href="<?php echo esc_url( $urls->whitelabel_url ); ?>" class="sui-button sui-button-blue" style="margin: 10px 0;">
							<?php esc_html_e( 'ACTIVATE', 'wpmudev' ); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>
			</td>
			<?php if ( $whitelabel_settings['enabled'] ): ?>
				<td style="flex:1">
					<?php printf( '<span style="text-align:center;"class="sui-tag sui-tag-sm sui-tag-branded">%s</span>', esc_html__( 'Active', 'wpmudev' ) ); ?>
				</td>
				<td>
					<a class="sui-button-icon" href="<?php echo esc_url( $urls->whitelabel_url ); ?>">
						<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
					</a>
				</td>
			<?php endif; ?>
		</tr>

		</tbody>

	</table>

	<?php // box footer. ?>
	<div class="sui-box-footer">
		<?php if ( 'free' !== $membership_data['membership'] ): ?>
			<a href="<?php echo esc_url( $urls->analytics_url ); ?>" class="sui-button sui-button-ghost">
				<i class="sui-icon-wrench-tool" aria-hidden="true"></i>
				<?php esc_html_e( 'Configure', 'wpmudev' ); ?>
			</a>
		<?php endif; ?>
	</div>
</div>
