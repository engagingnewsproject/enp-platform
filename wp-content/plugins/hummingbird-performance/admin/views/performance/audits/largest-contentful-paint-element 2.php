<?php
/**
 * Largest Contentful Paint element audit.
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
	<?php esc_html_e( 'A "content element" is an HTML element, such as:', 'wphb' ); ?>
</p>

<ol>
	<li><?php esc_html_e( 'An image element', 'wphb' ); ?></li>
	<li><?php esc_html_e( 'A video element', 'wphb' ); ?></li>
	<li><?php esc_html_e( 'An element with the background image loaded via the URL function (instead of declaring it in the CSS)', 'wphb' ); ?></li>
	<li><?php esc_html_e( 'Block-level elements such as <h1>, <h2>, <div>, <ul>, <table>, etc.', 'wphb' ); ?></li>

</ol>

<h4><?php esc_html_e( 'Status', 'wphb' ); ?></h4>
<?php
if ( isset( $audit->errorMessage ) && ! isset( $audit->score ) ) {
	$this->admin_notices->show_inline( /* translators: %s - error message */
		sprintf( esc_html__( 'Error: %s', 'wphb' ), esc_html( $audit->errorMessage ) ),
		'error'
	);
	return;
}

$this->admin_notices->show_inline(
	esc_html__( 'This is the element that was identified as the Largest Contentful Paint', 'wphb' ),
	'grey'
);
?>

<?php if ( $audit->details->items ) : ?>
	<table class="sui-table">
		<thead>
		<tr>
			<th><?php esc_html_e( 'Element', 'wphb' ); ?></th>
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
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>

<p>
	<?php
	printf( /* translators: %1$s - <a>, %2$s - </a> */
		esc_html__( 'Note: This Audit is purely informative but you can %1$slearn more about optimizing your Largest Contentful Paint here%2$s.', 'wphb' ),
		'<a href="https://web.dev/lcp/#how-to-improve-largest-contentful-paint-on-your-site" target="_blank">',
		'</a>'
	);
	?>
</p>
