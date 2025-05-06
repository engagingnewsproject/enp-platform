<?php
/**
 * Dashboard template: Resources widget.
 *
 * @since   4.0.0
 *
 * @var WPMUDEV_Dashboard_Ui            $this                  UI class.
 * @var WPMUDEV_Dashboard_Sui_Page_Urls $urls                  URLs class.
 * @var string                          $type                  Membership type.
 * @var bool                            $has_hosted_access     Has hosted site access.
 * @var bool                            $is_hosted_third_party Is hosting account on third party site.
 * @var array                           $membership_data       Membership data.
 *
 * @package WPMUDEV_Dashboard
 */

$resources = array(
	'documentation' => array(
		'title'      => __( 'Documentation', 'wpmudev' ),
		'icon'       => 'page',
		'url'        => $urls->documentation_url['dashboard'],
		'has_access' => true,
	),
	'forums'        => array(
		'title'      => __( 'Member Forums', 'wpmudev' ),
		'icon'       => 'community-people',
		'url'        => $urls->community_url,
		'has_access' => WPMUDEV_Dashboard::$api->is_support_allowed() || $has_hosted_access,
	),
	'blog'          => array(
		'title'      => __( 'Blog', 'wpmudev' ),
		'icon'       => 'blog',
		'url'        => $urls->blog_url,
		'has_access' => true,
	),
	'whip'          => array(
		'title'      => __( 'The Whip', 'wpmudev' ),
		'icon'       => 'wordpress',
		'url'        => $urls->whip_url,
		'has_access' => true,
	),
	'roadmap'       => array(
		'title'      => __( 'Product Roadmap', 'wpmudev' ),
		'icon'       => 'wpmudev-logo',
		'url'        => $urls->roadmap_url,
		'has_access' => true,
	),
);

if ( ( 'free' === $type || $is_hosted_third_party ) && ! $has_hosted_access ) {
	unset( $resources['forums'] );
}

?>

<div class="sui-box">

	<div class="sui-box-header">

		<h2 class="sui-box-title">
			<i class="sui-icon-help-support" aria-hidden="true"></i>
			<?php esc_html_e( 'Resources', 'wpmudev' ); ?>
		</h2>

	</div>

	<div class="sui-box-body">
		<p><?php esc_html_e( 'Here’s a bunch of our lesser-known but supremely helpful resources and usage guides.', 'wpmudev' ); ?></p>
	</div>

	<table class="sui-table dashui-table-tools dashui-resources">
		<tbody>
		<?php foreach ( $resources as $resource ) : ?>
			<tr>
				<td class="dashui-item-content">
					<h4 class="dashui-resources-title">
						<a href="<?php echo esc_url( $resource['url'] ); ?>">
								<span style="margin-right: 10px;">
									<i class="sui-icon-<?php echo esc_attr( $resource['icon'] ); ?>" aria-hidden="true"></i>
								</span>
							<?php echo esc_html( $resource['title'] ); ?>
							<?php if ( ! $resource['has_access'] ) : ?>
								<span class="sui-tag sui-tag-pro">
									<?php esc_html_e( 'Pro', 'wpmudev' ); ?>
								</span>
							<?php endif; ?>
						</a>
					</h4>
				</td>
				<td>
					<a class="sui-button-icon" href="<?php echo esc_url( $resource['url'] ); ?>">
						<i class="sui-icon-chevron-right" aria-hidden="true"></i>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ( ( 'free' !== $type && ! $is_hosted_third_party ) || $has_hosted_access ) : ?>
		<div class="sui-box-footer">
			<p class="sui-block-content-center sui-p-small" style="width: 100%;">
				<?php esc_html_e( 'Still stuck?', 'wpmudev' ); ?> <a href="https://wpmudev.com/hub/support/#wpmud-chat-pre-survey-modal" target="_blank"> <?php esc_html_e( 'Open a support ticket', 'wpmudev' ); ?> </a> <?php esc_html_e( "and we'll be happy to help you.", 'wpmudev' ); ?>
			</p>
		</div>
	<?php endif; ?>
</div>
