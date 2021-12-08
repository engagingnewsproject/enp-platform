<?php
/**
 * Avoid large layout shifts audit.
 *
 * @since 3.0.0
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
	<?php esc_html_e( 'Large layout shifts can create a frustrating experience for your visitors as they make your page appear visually jarring, as page elements appear suddenly, move around, and affect how your visitors interact with the page.', 'wphb' ); ?>
</p>

<p>
	<?php esc_html_e( 'Avoiding large layout shifts is essential in creating a smooth and streamlined experience for your visitors.', 'wphb' ); ?>
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

if ( isset( $audit->details->items ) && 0 === count( $audit->details->items ) ) {
	$this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any elements contributing to the CLS of your page.", 'wphb' ) );
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
				<th><?php esc_html_e( 'Element', 'wphb' ); ?></th>
				<th><?php esc_html_e( 'CLS Contribution', 'wphb' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $audit->details->items as $item ) : ?>
				<tr>
					<td>
						<?php echo esc_html( $item->node->nodeLabel ); ?><br/>
						<pre class="sui-code-snippet sui-no-copy">
							<?php echo esc_html( $item->node->snippet ); ?>
						</pre>
					</td>
					<td><?php echo (float) $item->score; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p>
		<?php
		printf( /* translators: %1$s - <a>, %2$s - </a> */
			esc_html__( 'Learn how to avoid sudden layout shifts to improve user experience in this amazing in-depth article by Google: %1$sOptimize Cumulative Layout Shift%2$s.', 'wphb' ),
			'<a href="https://web.dev/optimize-cls/" target="_blank">',
			'</a>'
		);
		?>
	</p>
<?php } ?>
