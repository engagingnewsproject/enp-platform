<?php
/**
 * Minify JavaScript audit.
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
	<?php esc_html_e( 'Compressing JavaScript files removes unnecessary code such as comments, formatting, and other non-critical information. This makes the file smaller allowing your pages to load faster.', 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any uncompressed JavaScript files.", 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - properly formatted bytes value */
			esc_html__( 'You can potentially save %s by minifying the following JavaScript files.', 'wphb' ),
			esc_html( \Hummingbird\Core\Utils::format_bytes( $audit->details->overallSavingsBytes, 0 ) )
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
		<?php if ( ! \Hummingbird\Core\Utils::is_member() || ! apply_filters( 'wpmudev_branding_hide_branding', false ) ) : ?>
			<p><?php esc_html_e( 'Use the Hummingbird Asset Optimization module to compress your JavaScript files. To minify, locate the JavaScript files in the assets page and click the compress button on the right. Hummingbird Pro users get access to the WPMU DEV CDN for additional savings.', 'wphb' ); ?></p>
		<?php else : ?>
			<p><?php esc_html_e( 'Use the Hummingbird Asset Optimization module to compress your JavaScript files. To minify, locate the JavaScript files in the assets page and click the compress button on the right.', 'wphb' ); ?></p>
		<?php endif; ?>
		<?php if ( $url = \Hummingbird\Core\Utils::get_admin_menu_url( 'minification' ) ) : ?>
			<a href="<?php echo esc_url( $url ); ?>" class="wphb-button-link">
				<?php esc_html_e( 'Configure Asset Optimization', 'wphb' ); ?>
			</a>
		<?php endif; ?>

		<h4><?php esc_html_e( 'Additional notes', 'wphb' ); ?></h4>
		<p><?php esc_html_e( 'Some of your files may not be hosted on your server. Hummingbird cannot compress JavaScript files hosted on a separate domain. If one of these files needs to be minimized, you can:', 'wphb' ); ?></p>
		<ol>
			<li><?php esc_html_e( 'Find where the asset is being added from (plugin, theme, or custom code) and replace it with an optimized file.', 'wphb' ); ?></li>
			<li><?php esc_html_e( 'Ignore this recommendation and continue using the file as is. While leaving files uncompressed may affect your 	performance score, it is recommended for essential plugins or themes when the files can\'t be altered.', 'wphb' ); ?></li>
		</ol>
	<?php endif; ?>
<?php endif; ?>
