<?php
/**
 * Serve static assets with an efficient cache policy audit.
 *
 * @since 2.0.0
 * @package Hummingbird
 *
 * @var stdClass $audit  Audit object.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h4><?php esc_html_e( 'Overview', 'wphb' ); ?></h4>
<p>
	<?php esc_html_e( 'Browsers download and cache (store) assets locally so that subsequent visits to your pages load much faster. You have the ability to specify how long cached assets are stored before the browser downloads a newer version. Ensuring your resources have reasonable expiry times will lead to faster page loads for repeat visitors.', 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any resource with an inefficient cache policy.", 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %d - number of resources */
			esc_html__( 'Your page is serving following %d resources with an inefficient cache policy.', 'wphb' ),
			absint( count( $audit->details->items ) )
		),
		\Hummingbird\Core\Modules\Performance::get_impact_class( $audit->score )
	);
	?>

	<?php if ( $audit->details->items ) : ?>
		<table class="sui-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'URL', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'Cache TTL', 'wphb' ); ?></th>
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
					<td>
						<?php
						if ( $item->cacheLifetimeMs ) {
							echo esc_html( \Hummingbird\Core\Utils::format_interval( $item->cacheLifetimeMs / 1000 ) );
						} else {
							esc_html_e( 'None', 'wphb' );
						}
						?>
					</td>
					<td><?php echo esc_html( \Hummingbird\Core\Utils::format_bytes( $item->totalBytes ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'Hummingbird is the fastest and most efficient plugin to enable browser caching. You can enable browser caching on all your resource types with the click of a button. We recommend setting an expiry time of at least 1 year for all file types.', 'wphb' ); ?></p>
	<?php if ( is_main_site() ) : ?>
		<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'caching' ) . '&view=caching' ); ?>" class="sui-button">
			<?php esc_html_e( 'Configure Browser Caching', 'wphb' ); ?>
		</a>
	<?php endif; ?>

	<h4><?php esc_html_e( 'Additional notes', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'Unfortunately, there is no way to control the caching headers of external resources served from another domain. Resources from other domains mentioned in the Status section are likely being added by a plugin or your theme. You can:', 'wphb' ); ?></p>
	<ol>
		<li><?php esc_html_e( 'Deactivate the theme and/or your plugins one by one to find the culprit, then remove it or find a comparable substitute.', 'wphb' ); ?></li>
		<li><?php esc_html_e( "Continue using the theme or plugin. This may be a perfectly valid option for services you just can't live without.", 'wphb' ); ?></li>
	</ol>
<?php endif; ?>
