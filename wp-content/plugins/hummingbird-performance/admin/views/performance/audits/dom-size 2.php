<?php
/**
 * Avoid an excessive DOM size audit.
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
	<?php esc_html_e( 'Everything inside your document is a node - HTML tags, text inside the tags and comments. These nodes have a hierarchical relationship, making a tree of nodes called the DOM tree. A large DOM tree harms your network efficiency and load performance as the browser has to parse lots of nodes that aren\'t displayed above-the-fold. Additionally, a large DOM tree increases memory usage, requires massive style calculations, and produces costly layout reflows.', 'wphb' ); ?>
</p>

<h4><?php esc_html_e( 'Status', 'wphb' ); ?></h4>
<?php if ( isset( $audit->errorMessage ) && ! isset( $audit->score ) ) {
	$this->admin_notices->show_inline(
		/* translators: %s - error message */
		sprintf( esc_html__( 'Error: %s', 'wphb' ), esc_html( $audit->errorMessage ) ),
		'error'
	);
	return;
}
?>
<?php if ( isset( $audit->score ) && 1 === $audit->score ) : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf(
			/* translators: %s - nodes in total */
			esc_html__( 'Nice! Your DOM only has %s in total.', 'wphb' ),
			esc_html( $audit->displayValue )
		)
	);
	?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf(
			/* translators: %s - nodes in total */
			esc_html__( 'Your DOM has %s in total.', 'wphb' ),
			esc_html( $audit->displayValue )
		),
		\Hummingbird\Core\Modules\Performance::get_impact_class( $audit->score )
	);
	?>

	<?php if ( $audit->details->items ) : ?>
		<table class="sui-table">
			<tbody>
			<tr>
				<td><strong><?php esc_html_e( 'Total DOM Nodes', 'wphb' ); ?></strong></td>
				<td>&nbsp;</td>
				<td><?php echo esc_html( $audit->details->items[0]->value ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Maximum DOM Depth', 'wphb' ); ?></strong></td>
				<td>
					<?php
					if ( isset( $audit->details->items[1]->element ) ) {
						echo esc_html( $audit->details->items[1]->element->value );
					}
					?>
				</td>
				<td><?php echo esc_html( $audit->details->items[1]->value ); ?></td>
			</tr>
			<tr>
				<td><strong><?php esc_html_e( 'Maximum Child Elements', 'wphb' ); ?></strong></td>
				<td>
					<?php
					if ( isset( $audit->details->items[2]->element ) ) {
						echo esc_html( $audit->details->items[2]->element->value );
					}
					?>
				</td>
				<td><?php echo esc_html( $audit->details->items[2]->value ); ?></td>
			</tr>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'The score depends solely on the total number of nodes found on the page. The median is 1400 nodes; however, for a perfect score, your DOM’s total nodes should be lower than 275. Try the following to improve your score:', 'wphb' ); ?></p>
	<ol>
		<li><?php esc_html_e( 'Most of the time, your theme is responsible for adding redundant DOM nodes to your site. Activate the default TwentyNinteen theme, and compare the performance score. If the score improves, that means your current theme is the culprit, and you should either use another efficiently coded theme or work with the developer to see what changes can be made.', 'wphb' ); ?></li>
		<li><?php esc_html_e( 'Since the total number of nodes depends on the size of the page, break large pages into multiple smaller ones to reduce the total node count.', 'wphb' ); ?></li>
	</ol>
<?php endif; ?>
