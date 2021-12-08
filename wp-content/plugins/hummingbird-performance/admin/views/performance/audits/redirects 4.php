<?php
/**
 * Avoid multiple page redirects audit.
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
	<?php esc_html_e( 'When a browser requests a resource that has been redirected, it must make an additional HTTP request at the new location to retrieve the page. This extra step can delay your page from loading by hundreds of milliseconds.', 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any unnecessary redirects.", 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %d - number of ms */
			esc_html__( 'You can potentially save %dms of page load time by avoiding the following redirects on your page.', 'wphb' ),
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
				<th><?php esc_html_e( 'Time Spent', 'wphb' ); ?></th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ( $audit->details->items as $item ) : ?>
				<tr>
					<?php foreach ( $audit->details->headings as $heading ) : ?>
						<td>
							<?php if ( 'url' === $heading->key ) : ?>
								<a href="<?php echo esc_html( $item->{$heading->key} ); ?>" target="_blank">
									<?php echo esc_html( $item->{$heading->key} ); ?>
								</a>
							<?php else : ?>
								<?php echo esc_html( $item->{$heading->key} ) . ' ms'; ?>
							<?php endif; ?>
						</td>
					<?php endforeach; ?>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<ol>
		<li><?php esc_html_e( 'The status section lists resources that are being redirected. Update the links to these resources to point to the new location.', 'wphb' ); ?></li>
		<li><?php esc_html_e( "If you're using redirects to divert mobile users to a mobile version of your page, consider using a responsive theme instead.", 'wphb' ); ?></li>
		<li><?php esc_html_e( 'Redirects can also be caused by a plugin or theme. Disabling plugins one at a time can help you find the culprit.', 'wphb' ); ?></li>
	</ol>
<?php endif; ?>
