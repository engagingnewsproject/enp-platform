<?php
/**
 * Defer unused CSS audit.
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
	<?php esc_html_e( "By default, a browser must download, parse, and process all the external stylesheets it encounters before it can be rendered on a user's screen. Removing or deferring unused rules in your stylesheet makes it load faster.", 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( 'Nice! Your site is not loading any unused CSS.', 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %s - properly formatted bytes value */
			esc_html__( 'You can save %s by defering the following CSS files.', 'wphb' ),
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

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<ol>
		<li><?php esc_html_e( "Use Hummingbird's Asset Optimization module to move critical styles inline.", 'wphb' ); ?></li>
		<li><?php esc_html_e( 'Combine non-critical styles, compress your stylesheets, and move them into the footer.', 'wphb' ); ?></li>
	</ol>
	<?php if ( $url = \Hummingbird\Core\Utils::get_admin_menu_url( 'minification' ) ) : ?>
		<a href="<?php echo esc_url( $url . '&enable-advanced-settings=true' ); ?>" class="wphb-button-link">
			<?php esc_html_e( 'Configure Asset Optimization', 'wphb' ); ?>
		</a>
	<?php endif; ?>
<?php endif; ?>
