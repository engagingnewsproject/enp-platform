<?php
/**
 * Defer offscreen images audit.
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
	<?php esc_html_e( 'In other words, deferring images below the fold means visitors can start interacting with your website without waiting for content further down the page to load = awesome.', 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any offscreen images which can speed up the page load by using lazy-loading.", 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %d - number of ms */
			esc_html__( 'You can potentially reduce the page load time by %dms using lazy-loading on the following images.', 'wphb' ),
			absint( $audit->details->overallSavingsMs )
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
				<th><?php esc_html_e( 'Savings', 'wphb' ); ?></th>
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
					<td><?php echo esc_html( \Hummingbird\Core\Utils::format_bytes( $item->wastedBytes ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<?php if ( ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
		<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
		<p>
			<?php
			$starting_link = '';
			$ending_link   = '';
			if ( $this->is_smush_installed() && $this->is_smush_enabled() && $this->is_smush_configurable() ) {
				$starting_link = '<a href="' . esc_html( \Hummingbird\Core\Utils::get_link( 'smush' ) ) . '" target="_blank">';
				$ending_link   = '</a>';
			}
			printf(
				/* translators: %1$s - link to Smush page, %2$s - closing a tag */
				esc_html__( 'Lazy loading is the term used to delay loading images below the fold (offscreen images). %1$sSmush%2$s comes packed with bullet-proof lazy loading, just enable the feature and configure any animation you want - no coding required.', 'wphb' ),
				$starting_link,
				$ending_link
			);
			?>
		</p>

		<?php if ( $this->is_smush_installed() && $this->is_smush_enabled() && $this->is_smush_configurable() ) : ?>
			<a href="<?php echo esc_url( menu_page_url( 'smush', false ) . '&view=lazy_load' ); ?>" class="sui-button">
				<?php esc_html_e( 'Configure Lazy load in Smush', 'wphb' ); ?>
			</a>
		<?php elseif ( $this->is_smush_installed() && ! $this->is_smush_enabled() && is_main_site() ) : ?>
			<?php
			if ( $this->is_smush_pro ) {
				$url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=wp-smush-pro/wp-smush.php', 'activate-plugin_wp-smush-pro/wp-smush.php' );
			} else {
				$url = wp_nonce_url( 'plugins.php?action=activate&amp;plugin=wp-smushit/wp-smush.php', 'activate-plugin_wp-smushit/wp-smush.php' );
			}
			?>
			<a href="<?php echo esc_url( $url ); ?>" target="_blank" class="sui-button">
				<?php esc_html_e( 'Activate Smush', 'wphb' ); ?>
			</a>
		<?php elseif ( is_main_site() ) : ?>
			<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_link( 'smush' ) ); ?>" target="_blank" class="sui-button">
				<?php esc_html_e( 'Install Smush', 'wphb' ); ?>
			</a>
		<?php endif; ?>
	<?php endif; ?>
<?php endif; ?>
