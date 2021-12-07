<?php
/**
 * Avoid enormous network payloads audit.
 *
 * @since 2.0.0
 * @package Hummingbird
 *
 * @var stdClass                             $audit  Audit object.
 * @var \Hummingbird\Admin\Pages\Performance $this   Performance page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h4><?php esc_html_e( 'Overview', 'wphb' ); ?></h4>
<p>
	<?php esc_html_e( 'The time your page takes to load is directly proportional to the total size of network requests it makes. Reducing the overall size of resources visitors have to load will increase your score here. The recommended size is around 1600Kb for the average site, which would mean a load time of less than 10s for poor 3G connections.', 'wphb' ); ?>
</p>

<h4><?php esc_html_e( 'Status', 'wphb' ); ?></h4>
<?php if ( isset( $audit->errorMessage ) && ! isset( $audit->score ) ) {
	$this->admin_notices->show_inline( /* translators: %s - error message */
		sprintf( esc_html__( 'Error: %s', 'wphb' ), esc_html( $audit->errorMessage ) ),
		'error'
	);
	return;
}
?>
<?php if ( isset( $audit->score ) && 1 === $audit->score ) : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - size in kb */
			esc_html__( 'Nice! Your page makes only %s of total network requests.', 'wphb' ),
			esc_html( str_replace( 'Total size was ', '', $audit->displayValue ) )
		)
	);
	?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - size in kb */
			esc_html__( 'Your page makes %s of total network requests and following are the resources with large size.', 'wphb' ),
			esc_html( str_replace( 'Total size was ', '', $audit->displayValue ) )
		),
		\Hummingbird\Core\Modules\Performance::get_impact_class( $audit->score )
	);
	?>

	<?php if ( $audit->details->items ) : ?>
		<table class="sui-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'URL', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'Size', 'wphb' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $audit->details->items as $item ) : ?>
				<tr>
					<td>
						<a href="<?php echo esc_html( $item->url ); ?>" target="_blank">
							<?php echo esc_html( $item->url ); ?>
						</a>
					</td>
					<td><?php echo esc_html( \Hummingbird\Core\Utils::format_bytes( $item->totalBytes ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<ol>
		<li>
			<p><?php esc_html_e( 'Optimizing your resources removes unnecessary or redundant bytes of code and hence reduces payload size and script parse time. Hummingbird’s Asset Optimization module can help you to minify your resources.', 'wphb' ); ?></p>
			<?php if ( $url = \Hummingbird\Core\Utils::get_admin_menu_url( 'minification' ) ) : ?>
				<a href="<?php echo esc_url( $url ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Asset Optimization', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
		<li>
			<p><?php esc_html_e( 'Text-based resources such as your HTML, CSS, and JavaScript files should be served with GZIP compression to minimize total network bytes. Hummingbird can enable GZIP compression for all the compressible resources.', 'wphb' ); ?></p>
			<?php if ( is_main_site() ) : ?>
				<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'gzip' ) ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Gzip Compression', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
		<li>
			<p><?php esc_html_e( 'Caching your files can reduce network cost as the browser can serve cached resources instead of fetching them from network. You can configure file caching using Hummingbird.', 'wphb' ); ?></p>
			<?php if ( is_main_site() ) : ?>
				<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'caching' ) . '&view=caching' ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Browser Compression', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
		<li>
			<p><?php esc_html_e( 'Serve compressed images, and serve them in WebP format whenever possible since it provides better compression than PNG or JPEG, which means faster downloads and less data consumption. Smush has a blazing-fast CDN which can automatically convert and serve your images in WebP format to compatible browsers or gracefully fall back to original PNGs or JPEGs on non-compatible browsers.', 'wphb' ); ?></p>
			<?php if ( $this->is_smush_installed() && $this->is_smush_enabled() && $this->is_smush_configurable() ) : ?>
				<a href="<?php menu_page_url( 'smush' ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Smush', 'wphb' ); ?>
				</a>
			<?php elseif ( $this->is_smush_installed() && ! $this->is_smush_enabled() && is_main_site() ) : ?>
				<?php
				if ( $this->is_smush_pro ) {
					$url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=wp-smush-pro/wp-smush.php', 'activate-plugin_wp-smush-pro/wp-smush.php' );
				} else {
					$url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=wp-smushit/wp-smush.php', 'activate-plugin_wp-smushit/wp-smush.php' );
				}
				?>
				<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="wphb-button-link">
					<?php esc_html_e( 'Activate Smush', 'wphb' ); ?>
				</a>
			<?php elseif ( is_main_site() ) : ?>
				<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_link( 'smush' ) ); ?>" target="_blank" class="wphb-button-link">
					<?php esc_html_e( 'Install Smush', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
	</ol>

	<?php if ( ! $this->is_smush_installed() && ! \Hummingbird\Core\Utils::is_member() && is_main_site() ) : ?>
		<div class="wphb-upsell-performance-row wphb-negative-margin">
			<img class="sui-image sui-upsell-image"
				src="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/smush-share-widget.png' ); ?>"
				srcset="<?php echo esc_url( WPHB_DIR_URL . 'admin/assets/image/smush-share-widget@2x.png' ); ?> 2x"
				alt="<?php esc_attr_e( 'Get WP Smush Pro', 'wphb' ); ?>">
			<?php
			$this->admin_notices->show_inline(
				sprintf( /* translators: %1$s - link to Smush project page, %2$s - closing a tag */
					esc_html__( "Upgrade to %1\$sSmush Pro%2\$s, and start serving your images in WebP format using the Smush CDN. You'll also get 2x better compression on your images along with other pro features. %3\$sTry it out today with a free WMPU DEV membership%2\$s!", 'wphb' ),
					'<a href="' . esc_html( \Hummingbird\Core\Utils::get_link( 'smush', 'hummingbird_report_smush_upsell_link' ) ) . '" target="_blank">',
					'</a>',
					'<a href="#" data-modal-open="wphb-upgrade-membership-modal" data-modal-open-focus="upgrade-to-pro-button" data-modal-mask="true">'
				),
				'upsell',
				sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
					esc_html__( '%1$sLearn More%2$s', 'wphb' ),
					'<a href="' . esc_html( \Hummingbird\Core\Utils::get_link( 'smush', 'hummingbird_report_smush_upsell_link' ) ) . '" target="_blank" class="sui-button sui-button-purple">',
					'</a>'
				)
			);
			?>
		</div>
	<?php endif; ?>
<?php endif; ?>
