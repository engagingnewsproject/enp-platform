<?php
/**
 * Template for Services widget on the Main Dashboard page.
 *
 * @package template
 */

$site_id      = isset( $membership_data['hub_site_id'] ) ? $membership_data['hub_site_id'] : '';
$url_base     = sprintf( '%shub2/site/%s/', $urls->remote_site, $site_id );
$uptime_url   = sprintf( '%suptime', $url_base );
$automate_url = sprintf( '%spluginsThemes/automate', $url_base );
$reports_url  = sprintf( '%sreports', $url_base );
$services     = array(
	array(
		'class' => ( isset( $membership_data['services'] ) && $membership_data['services']['uptime'] ? 'sui-tag sui-tag-blue sui-tag-sm' : 'sui-tag sui-tag-sm' ),
		'text'  => ( isset( $membership_data['services'] ) && $membership_data['services']['uptime'] ? __( 'Active', 'wpmudev' ) : __( 'Inactive', 'wpmudev' ) ),
		'title' => 'Uptime',
		'url'   => esc_url( $uptime_url ),
		'icon'  => 'hummingbird',
	),
	array(
		'class' => ( isset( $membership_data['services'] ) && $membership_data['services']['automate'] ? 'sui-tag sui-tag-blue sui-tag-sm' : 'sui-tag sui-tag-sm' ),
		'text'  => ( isset( $membership_data['services'] ) && $membership_data['services']['automate'] ? __( 'Active', 'wpmudev' ) : __( 'Inactive', 'wpmudev' ) ),
		'title' => 'Automate',
		'url'   => esc_url( $automate_url ),
		'icon'  => 'defender',
	),
	array(
		'class' => ( isset( $membership_data['services'] ) && $membership_data['services']['reports'] ? 'sui-tag sui-tag-blue sui-tag-sm' : 'sui-tag sui-tag-sm' ),
		'text'  => ( isset( $membership_data['services'] ) && $membership_data['services']['reports'] ? __( 'Active', 'wpmudev' ) : __( 'Inactive', 'wpmudev' ) ),
		'title' => 'Reports',
		'url'   => esc_url( $reports_url ),
		'icon'  => 'smart-crawl',
	),
);
?>
<div class="sui-box">

	<div class="sui-box-header">

		<h2 class="sui-box-title">
			<i class="sui-icon-hub" aria-hidden="true"></i>
			<?php esc_html_e( 'Services', 'wpmudev' ); ?>
		</h2>
<?php if ( 'free' === $membership_data['membership'] ) : ?>
				<div class="sui-actions-left">

				<span class="sui-tag sui-tag-purple sui-dashboard-expired-pro-tag">
					<?php esc_html_e( 'Pro', 'wpmudev' ); ?>
				</span>
			</div>
<?php endif; ?>
	</div>

	<?php // box body. ?>
	<div class="sui-box-body">
		<p><?php esc_html_e( 'Monitor and automate the critical pieces of your site.', 'wpmudev' ); ?></p>
	</div>

	<?php // active plugin table. ?>
	<table class="sui-table dashui-table-tools dashui-services">
		<tbody>
			<?php foreach ( $services as $service ) : ?>
				<tr>
					<td class="dashui-item-content">
						<h4>
							<a href="<?php echo esc_url( $service['url'] ); ?>" target="_blank">
								<span style="margin-right:10px;"><i class="sui-icon-<?php echo esc_attr( $service['icon'] ); ?>" aria-hidden="true"></i></span>
								<?php echo esc_html( $service['title'] ); ?>
							</a>
						</h4>
					</td>
				<?php if ( 'free' !== $membership_data['membership'] ) : ?>
					<td style="flex:1;">
						<span class="<?php echo esc_attr( $service['class'] ); ?>"> <?php echo esc_html( $service['text'] ); ?></span>
					</td>
					<td>
						<a class="sui-button-icon" href="<?php echo esc_url( $service['url'] ); ?>" target="_blank">
							<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
						</a>
					</td>
<?php else : ?>
					<td>
						<a class="sui-button-icon" href="#">
							<i class="sui-icon-lock" aria-hidden="true"></i>
						</a>
					</td>
<?php endif; ?>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php // box footer. ?>
	<div class="sui-box-footer">
<?php if ( 'free' !== $membership_data['membership'] ) : ?>

		<a href="<?php echo esc_url( $urls->hub_url ); ?>" class="sui-button sui-button-ghost">
			<i class="sui-icon-eye" aria-hidden="true"></i>
			<?php esc_html_e( 'THE HUB', 'wpmudev' ); ?>
		</a>

<?php endif; ?>
	</div>

</div>
