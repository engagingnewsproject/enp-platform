<?php
/**
 * Preconnect to required origins audit.
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
		esc_html__( 'Establishing a connection with a third-party origin often involves significant time as it involves DNS lookups, redirects, and several round trips. Whenever your site needs to fetch resources from a third-party origin such as a CDN, you should consider pre-connecting to that origin to make your application feel snappier.', 'wphb' ),
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
<?php if ( isset( $audit->score ) && 1 === $audit->score ) : ?>
	<?php $this->admin_notices->show_inline( esc_html__( 'Nice! No issues found.', 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %d - number of ms */
			esc_html__( 'You can potentially save %dms by pre-connecting to the origin of following resources.', 'wphb' ),
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
	<p>
		<?php
		printf(
			/* translators: %s - preconnect attribute */
			esc_html__( 'You can use %s to inform the browser that your page intends to establish a connection to another origin and that you’d like the process to start as soon as possible. As an example, if your page is fetching multiple resources from another origin say https://example.com, you can add the following code inside the <head>. ', 'wphb' ),
			'<strong>&lt;link rel="preconnect"&gt;</strong>'
		);
		?>
	</p>
	<?php $code = '<span style="color:#3B78E7 !important">&lt;link</span> <span style="color:#8D00B1 !important">rel=</span>"preconnect" <span style="color:#8D00B1 !important">href=</span>"https://example.com"<span style="color:#3B78E7 !important">&gt;</span>'; ?>
	<pre class="sui-code-snippet sui-no-copy" style="color:#1ABC9C"><?php echo wp_kses_post( $code ); ?></pre>
	<p><?php esc_html_e( 'The browser won’t begin fetching the resources before it needs them, but at least it can handle the connection aspects ahead of time, saving the user from waiting for several roundtrips when your browser is fetching the resources from this origin.', 'wphb' ); ?></p>
<?php endif; ?>
