<?php
/**
 * Preload key requests audit.
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
	<?php
	printf(
		/* translators: % - <link rel=preload> */
		esc_html__( 'The late-discovery of critical resources hidden in CSS or JS files can delay the first render, the rendering of text, or loading of critical parts of a page. Use %s to tell the browser to fetch critical resources earlier.', 'wphb' ),
		'<strong>&lt;link rel=preload&gt;</strong>'
	);
	?>
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
<?php if ( ( isset( $audit->score ) && 1 === $audit->score ) || empty( $audit->details->items ) ) : ?>
	<?php $this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any resource which needs to use preload declarative.", 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %1$d - number of ms, %2$d - number of resources */
			esc_html__( 'You can potentially save %1$dms by preloading the following %2$d resources.', 'wphb' ),
			absint( $audit->details->overallSavingsMs ),
			count( $audit->details->items )
		),
		'grey'
	);
	?>

	<?php if ( $audit->details->items ) : ?>
		<table class="sui-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'URL', 'wphb' ); ?></th>
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
					<td><?php echo absint( $item->wastedMs ) . ' ms'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'Use the example statement to instruct your browser to download key resources as early as possible.', 'wphb' ); ?></p>
	<?php $code = '<span style="color:#3B78E7 !important">&lt;link</span> <span style="color:#8D00B1 !important">rel=</span>"preload" <span style="color:#8D00B1 !important">href=</span>"late-discovered-styles.css" <span style="color:#8D00B1 !important">as=</span>"style"<span style="color:#3B78E7 !important">&gt;</span>'; ?>
	<pre class="sui-code-snippet sui-no-copy" style="color:#1ABC9C"><?php echo wp_kses_post( $code ); ?></pre>
	<p>
		<?php
		printf(
			/* translators: %1$s - as attribute, %2$s - opening a tag, %3$s - closing a tag */
			esc_html__( 'The %1$s attribute tells the browser the type of the resource and hence helps to set the priority accordingly. A few common values for the %1$s attribute are script, style, font, image. Click %2$shere%3$s to see the complete list.', 'wphb' ),
			'<strong style="color:#8D00B1">as</strong>',
			'<a href="https://fetch.spec.whatwg.org/#concept-request-destination" target="_blank">',
			'</a>'
		);
		?>
	</p>
<?php endif; ?>
