<?php
/**
 * White-label widget template.
 *
 * @var bool $whitelabel_allowed  Is white label allowed?.
 * @var array                           $whitelabel_settings Whitelabel settings.
 * @var array                           $membership_data     Membership data.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls                URL class.
 *
 * @package templates
 */

// Setup activation URL.
$activate_url = add_query_arg(
	array(
		'status' => 'activate',
		'action' => 'whitelabel-setup',
	),
	$urls->whitelabel_url
);
$activate_url = wp_nonce_url( $activate_url, 'whitelabel-setup', 'hash' );

?>
<div class="sui-box">

	<?php // Title area. ?>
	<div class="sui-box-header">
		<h2 class="sui-box-title">
			<i class="sui-icon-monitor" aria-hidden="true"></i>
			<?php esc_html_e( 'White Label', 'wpmudev' ); ?>
		</h2>
		<?php if ( ! $whitelabel_allowed ) : ?>
			<div class="sui-actions-left">
				<span class="sui-tag sui-tag-pro">
					<?php esc_html_e( 'Pro', 'wpmudev' ); ?>
				</span>
			</div>
		<?php endif; ?>
	</div>
	<?php // Body area. ?>

	<div class="sui-box-body">
		<?php // Body area, description. ?>
		<?php if ( ! $whitelabel_allowed ) : ?>
		<p><?php esc_html_e( 'Remove WPMU DEV branding from all our plugins and replace it with your own branding for your clients. An active WPMU DEV membership is required.', 'wpmudev' ); ?></p>
	</div>
	<?php else : ?>
	<p><?php esc_html_e( 'Remove WPMU DEV branding from all our plugins and replace it with your own branding for your clients.', 'wpmudev' ); ?></p>
	<?php // Body area, not activated. ?>
	<?php if ( ! $whitelabel_settings['enabled'] ) : ?>
	<a href="<?php echo esc_url( $activate_url ); ?>" class="sui-button sui-button-blue" style="margin: 10px 0;">
		<?php esc_html_e( 'ACTIVATE', 'wpmudev' ); ?>
	</a>
</div>
<?php else : // Whitelabeling activated. ?>
	</div>
	<table class="sui-table dashui-table-tools dashui-services">
		<tbody>
		<tr>
			<td class="dashui-item-content">
				<h4>
					<?php esc_html_e( 'White Labeling', 'wpmudev' ); ?>
				</h4>
			</td>
			<td style="width: 125px">
				<span class="sui-tag sui-tag-blue sui-tag-sm"><?php esc_html_e( 'Active', 'wpmudev' ); ?></span>
			</td>
			<td>
				<a class="sui-button-icon" href="<?php echo esc_url( $urls->whitelabel_url ); ?>">
					<i class="sui-icon-widget-settings-config" aria-hidden="true"></i>
				</a>
			</td>
		</tr>
	</table>
	<?php // Body area, links. ?>
	<div class="sui-box-footer">
		<a href="<?php echo esc_url( $urls->whitelabel_url ); ?>" class="sui-button sui-button-ghost">
			<i class="sui-icon-eye" aria-hidden="true"></i>
			<?php esc_html_e( 'Configure', 'wpmudev' ); ?>
		</a>
	</div>
<?php endif; ?>
<?php endif; ?>
</div>