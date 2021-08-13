<?php
/**
 * Minimize main-thread work audit.
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
	<?php esc_html_e( 'This audit breaks down your main network thread into its different tasks. The browser generally spends the most time parsing, compiling, and executing your JavaScript. In most cases, delivering smaller JavaScript payloads can reduce the time the browser spends on your main network thread.', 'wphb' ); ?>
</p>

<h4><?php esc_html_e( 'Status', 'wphb' ); ?></h4>
<?php if ( isset( $audit->errorMessage ) && ! isset( $audit->score ) ) {
	$this->admin_notices->show_inline( /* translators: %s - error message */
		sprintf( esc_html__( 'Error: %s', 'wphb' ), esc_html( $audit->errorMessage ) ),
		'error'
	);
	return;
}

if ( isset( $audit->score ) && 1 === $audit->score ) {
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - time to complete in seconds */
			esc_html__( 'Nice! Your main thread just took %s to complete and following is the breakdown of your main thread.', 'wphb' ),
			esc_html( $audit->displayValue )
		)
	);
} else {
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - time to complete in seconds */
			esc_html__( 'Your main thread took %s to complete and following is the breakdown of your main thread.', 'wphb' ),
			esc_html( $audit->displayValue )
		),
		\Hummingbird\Core\Modules\Performance::get_impact_class( $audit->score )
	);
}
?>

<?php if ( $audit->details->items ) : ?>
	<table class="sui-table">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Category', 'wphb' ); ?></th>
			<th><?php esc_html_e( 'Time Spent', 'wphb' ); ?></th>
		</tr>
		</thead>
		<tbody>
		<?php foreach ( $audit->details->items as $item ) : ?>
			<tr>
				<td><?php echo esc_html( $item->groupLabel ); ?></td>
				<td><?php echo esc_html( round( $item->duration ) . ' ms' ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<?php if ( ! isset( $audit->score ) || 1 !== $audit->score ) : ?>
	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<ol>
		<li>
			<p><?php esc_html_e( 'Optimizing JavaScript files removes unnecessary or redundant bytes of code and hence reduces payload size and script parse time. Hummingbird’s Asset Optimization module can help you to minify your JavaScript files.', 'wphb' ); ?></p>
			<?php if ( $url = \Hummingbird\Core\Utils::get_admin_menu_url( 'minification' ) ) : ?>
				<a href="<?php echo esc_url( $url ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Asset Optimization', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
		<li>
			<p><?php esc_html_e( 'JavaScript files should be served with GZIP compression to minimize total network bytes. Fewer bytes downloaded means faster page loads. Hummingbird can help compress your JavaScript files.', 'wphb' ); ?></p>
			<?php if ( is_main_site() ) : ?>
				<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'gzip' ) ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Gzip Compression', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
		<li>
			<p><?php esc_html_e( 'Caching your JavaScript files can reduce network cost as the browser can serve cached resources instead of fetching them from network. You can configure caching of your JavaScript files using Hummingbird.', 'wphb' ); ?></p>
			<?php if ( is_main_site() ) : ?>
				<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'caching' ) . '&view=caching' ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Browser Compression', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
	</ol>

	<h4><?php esc_html_e( 'Additional notes', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'Unfortunately, there is no way to optimize scripts served from another domain. Scripts from other domains mentioned in the Status section are likely being added by a plugin or your theme. You can:', 'wphb' ); ?></p>
	<ol>
		<li><?php esc_html_e( 'Deactivate the theme and/or your plugins one by one to find the culprit, then remove it or find a comparable substitute.', 'wphb' ); ?></li>
		<li><?php esc_html_e( "Continue using the theme or plugin. This may be a perfectly valid option for services you just can't live without.", 'wphb' ); ?></li>
	</ol>
<?php endif; ?>
