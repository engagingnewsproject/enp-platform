<?php
/**
 * Avoids large JavaScript libraries with smaller alternatives audit.
 *
 * @since 3.0.0
 * @package Hummingbird
 *
 * @var stdClass $audit  Audit object.
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h4><?php esc_html_e( 'Overview', 'wphb' ); ?></h4>
<p>
	<?php esc_html_e( 'Avoiding large JavaScript libraries can help prevent a large JavaScript payload for your page. This, in turn, reduces the time needed by the browser to download, parse, and execute JavaScript files.', 'wphb' ); ?>
</p>

<p>
	<?php esc_html_e( 'It is always preferable to use smaller yet functionally equivalent JavaScript libraries to prevent a large JavaScript bundle size.', 'wphb' ); ?>
</p>

<h4><?php esc_html_e( 'Status', 'wphb' ); ?></h4>
<?php
if ( isset( $audit->errorMessage ) && ! isset( $audit->score ) ) {
	$this->admin_notices->show_inline( /* translators: %s - error message */
		sprintf( esc_html__( 'Error: %s', 'wphb' ), esc_html( $audit->errorMessage ) ),
		'error'
	);
	return;
}

if ( isset( $audit->score ) && 1 === $audit->score ) {
	$this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any large libraries to replace", 'wphb' ) );
} else {
	$this->admin_notices->show_inline(
		esc_html__( 'These DOM elements contribute the most to the CLS of the page', 'wphb' ),
		'grey'
	);
	?>
	<?php if ( $audit->details->items ) : ?>
		<table class="sui-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Library', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'Transfer Size', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'Potential Savings', 'wphb' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $audit->details->items as $item ) : ?>
				<tr>
					<td>
						<?php
						echo esc_html( $item->source );
						$total_bytes = $item->totalBytes;
						if ( isset( $item->subItems ) && ! empty( $item->subItems ) && isset( $item->subItems->items ) && ! empty( $item->subItems->items ) ) {
							foreach ( $item->subItems->items as $subitem ) {
								if ( isset( $subitem->sourceTransferBytes ) ) {
									$total_bytes += $subitem->sourceTransferBytes;
								}

								echo '<br>' . '&nbsp;&mdash;&nbsp;' . esc_html( $subitem->url );
							}
						}
						?>
					</td>
					<td><?php echo esc_html( Utils::format_bytes( $total_bytes ) ); ?></td>
					<td><?php echo esc_html( Utils::format_bytes( $item->wastedBytes ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p>
		<?php
		printf( /* translators: %1$s - <a>, %2$s - </a> */
			esc_html__( 'You can focus on optimizing your dependencies in order to achieve significant reductions in JavaScript library size. %1$sLearn more about optimizing your dependencies here%2$s.', 'wphb' ),
			'<a href="https://developers.google.com/web/fundamentals/performance/webpack/decrease-frontend-size#optimize_dependencies" target="_blank">',
			'</a>'
		);
		?>
	</p>
<?php } ?>
