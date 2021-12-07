<?php
/**
 * Reduce JavaScript execution time audit.
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
	<?php esc_html_e( "This audit measures the total impact of JavaScript on your page's load performance. JavaScript can slow down a page in many ways, including parsing, compiling, executing, as well as network and memory usage. Reducing the size of your JS files can dramatically improve this metric. Ideally, the JavaScript bootup time should be less than 500ms.", 'wphb' ); ?>
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
	$message = esc_html__( 'Nice! Your page has a very low JavaScript bootup time.', 'wphb' );
	if ( isset( $audit->displayValue ) ) {
		$message = sprintf( /* translators: %s - time in seconds */
			esc_html__( 'Nice! Your page has a very low JavaScript bootup time i.e %s', 'wphb' ),
			esc_html( $audit->displayValue )
		);
	}
	$this->admin_notices->show_inline( $message );
	?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - number of seconds */
			esc_html__( 'Your JavaScript bootup time is %s. Following are the scripts behind the high bootup time.', 'wphb' ),
			esc_html( $audit->displayValue )
		),
		\Hummingbird\Core\Modules\Performance::get_impact_class( $audit->score )
	);
	?>

	<?php if ( $audit->details->items ) : ?>
		<table class="sui-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'URL', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'Evaluation', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'Parsing', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'Total', 'wphb' ); ?></th>
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
					<td><?php echo round( $item->scripting ) . ' ms'; ?></td>
					<td><?php echo round( $item->scriptParseCompile ) . ' ms'; ?></td>
					<td><?php echo round( $item->total ) . ' ms'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

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
			<p><?php esc_html_e( 'JavaScript files should be served with GZIP compression to minimize total network bytes. Fewer bytes downloaded means faster page loads. Hummingbird can help you compress your JavaScript files.', 'wphb' ); ?></p>
			<?php if ( is_main_site() ) : ?>
				<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'gzip' ) ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Gzip Compression', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
		<li>
			<p><?php esc_html_e( 'Caching your JavaScript files can reduce network cost as the browser can serve cached resources instead of fetching them from the network. You can configure caching of your JavaScript files using Hummingbird.', 'wphb' ); ?></p>
			<?php if ( is_main_site() ) : ?>
				<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'caching' ) . '&view=caching' ); ?>" class="wphb-button-link">
					<?php esc_html_e( 'Configure Browser Compression', 'wphb' ); ?>
				</a>
			<?php endif; ?>
		</li>
	</ol>

	<h4><?php esc_html_e( 'Additional notes', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'Unfortunately, there is no way to improve bootup time for scripts served from another domain. Scripts from other domains mentioned in the Status section are likely being added by a plugin or your theme. You can:', 'wphb' ); ?></p>
	<ol>
		<li><?php esc_html_e( 'Deactivate the theme and/or your plugins one by one to find the culprit, then remove it or find a comparable substitute.', 'wphb' ); ?></li>
		<li><?php esc_html_e( "Continue using the theme or plugin. This may be a perfectly valid option for services you just can't live without.", 'wphb' ); ?></li>
	</ol>
<?php endif; ?>
