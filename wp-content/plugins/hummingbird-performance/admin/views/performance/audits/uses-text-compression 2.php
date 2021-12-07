<?php
/**
 * Enable text compression audit.
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
	<?php esc_html_e( 'Compressing text-based resources such as HTML, CSS, and JavaScript with GZIP shrinks your files, make your site faster, and improve your pages load score.', 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( 'Nice! You have compression enabled for your text based resources.', 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %d - number of ms */
			esc_html__( 'You can potentially save %dms by serving the following resources with GZIP compression.', 'wphb' ),
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

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p><?php esc_html_e( "You can easily enable GZIP compression for any local files using the GZIP Compression tab. For any externally hosted resources, you'll have to make sure the provider enables the compression there.", 'wphb' ); ?></p>

	<?php if ( is_main_site() ) : ?>
		<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_admin_menu_url( 'gzip' ) ); ?>" class="sui-button">
			<?php esc_html_e( 'Configure Gzip Compression', 'wphb' ); ?>
		</a>
	<?php endif; ?>
<?php endif; ?>
