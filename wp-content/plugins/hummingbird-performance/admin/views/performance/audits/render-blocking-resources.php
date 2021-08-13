<?php
/**
 * Eliminate render-blocking resources audit.
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
	<?php esc_html_e( 'Render-blocking resources are CSS, JS, or font files that are loading before the rest of the content on your page. This can add seconds to your page load time.', 'wphb' ); ?>
</p>
<p>
	<?php esc_html_e( 'Improve page load speed by deferring all non-critical style scripts and loading critical scripts first.', 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( 'No render blocking resources found.', 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf( /* translators: %d - number of ms */
			esc_html__( 'First paint of your page is not rendered without waiting for the following resources to load. You can potentially save %dms by eliminating these resources.', 'wphb' ),
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
					<td><?php echo absint( $item->wastedMs ) . ' ms'; ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'Hummingbird helps you move render-blocking scripts to your footer, and combines blocking CSS in your header. Follow the steps below to improve this score:', 'wphb' ); ?></p>
	<ol>
		<li><?php esc_html_e( 'Jump over to the Hummingbird / Asset Optimization tab and run a fresh file check.', 'wphb' ); ?></li>
		<li><?php esc_html_e( 'After making any initial optimization, switch to the "Inline CSS" option to inline the styles which are critical for first paint of your page. Combine as many of the non-critical styles as you can, compress them and then move them to the footer.', 'wphb' ); ?></li>
		<li><?php esc_html_e( 'Move critical scripts to the footer and defer the non-critical scripts using the option "Force load this file after the page has loaded".', 'wphb' ); ?></li>
	</ol>
	<?php if ( $url = \Hummingbird\Core\Utils::get_admin_menu_url( 'minification' ) ) : ?>
		<a href="<?php echo esc_url( $url . '&enable-advanced-settings=true' ); ?>" class="sui-button">
			<?php esc_html_e( 'Configure Asset Optimization', 'wphb' ); ?>
		</a>
	<?php endif; ?>
	<p>
		<strong><?php esc_html_e( 'Note: It can be tough to get a perfect score for this rule.', 'wphb' ); ?></strong>
		<?php esc_html_e( 'Some plugins and themes are not able to handle deferred scripts. If the combine and minify all CSS option does not work on your setup, we recommend trying multiple combinations to find the best configuration for your site.', 'wphb' ); ?>
	</p>
<?php endif; ?>
