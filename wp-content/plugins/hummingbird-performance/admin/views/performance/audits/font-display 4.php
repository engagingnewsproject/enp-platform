<?php
/**
 * Ensure text remains visible during webfont load audit.
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
		/* translators: %1$s - <strong>, %2$s - </strong> */
		esc_html__( "When you use web fonts on your website, browsers have to download them before any text can be displayed. Most browsers have a maximum timeout, after which a web font will be replaced with a fallback font. It's recommended to use a %1\$sfont-display%2\$s descriptor in your %1\$s@font-face%2\$s rule to control how text renders when web font download delays occur.", 'wphb' ),
		'<strong>',
		'</strong>'
	);
	?>
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
	<?php $this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any web font loading without font-display CSS rule.", 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		esc_html__( 'Your page is not using font-display rule when loading the following web fonts.', 'wphb' ),
		\Hummingbird\Core\Modules\Performance::get_impact_class( $audit->score )
	);
	?>

	<?php if ( $audit->details->items ) : ?>
		<table class="sui-table">
			<thead>
			<tr>
				<th><?php esc_html_e( 'Font URL', 'wphb' ); ?></th>
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
					<td><?php echo esc_html( round( $item->wastedMs ) . ' ms' ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p>
		<?php
		printf(
			/* translators: %1$s - <strong>, %2$s - </strong>, %3$s - <strong> with font color */
			esc_html__( 'To add the %1$sfont-display%2$s property for Google Fonts, you can pass the desired value in the query string %3$sdisplay%2$s parameter as shown in the example below:', 'wphb' ),
			'<strong>',
			'</strong>',
			'<strong style="color:#8D00B1 !important">'
		);
		?>
	</p>
	<pre class="sui-code-snippet sui-no-copy">https://fonts.googleapis.com/css?family=Roboto&<span style="color:#3B78E7 !important;">display</span>=<span style="color:#1ABC9C !important">swap</span></pre>
	<p>
		<?php
		printf(
			/* translators: %1$s - <strong>, %2$s - </strong> */
			esc_html__( 'For fonts hosted locally, add the %1$sfont-display%2$s property to the @font-face CSS rule as shown below:', 'wphb' ),
			'<strong>',
			'</strong>'
		);
		?>
	</p>
	<pre class="sui-code-snippet sui-no-copy"><span style="color:#8D00B1 !important">@font-face{</span>
	font-family: <span style="color:#1ABC9C !important">'myWebFont'</span>;
	font-display: <span style="color:#3B78E7 !important;">swap</span>;
	src: <span style="color:#3B78E7 !important;">url</span>(<span style="color:#1ABC9C !important">'myfont.woff2'</span>) <span style="color:#3B78E7 !important;">format</span>(<span style="color:#1ABC9C !important">'woff2'</span>);
<span style="color:#8D00B1 !important">}</span></pre>
	<p>
		<?php
		printf(
			/* translators: %1$s - <strong>, %2$s - </strong>, %3$s - link, %4$s - closing a tag */
			esc_html__( 'The %1$sfont-display%2$s supports "%1$sauto | block | swap | fallback | optional%2$s" values. Try different values to achieve the desired result. You can read about the different values %3$shere%4$s.', 'wphb' ),
			'<strong>',
			'</strong>',
			'<a href="https://developers.google.com/web/updates/2016/02/font-display" target="_blank">',
			'</a>'
		);
		?>
	</p>

	<h4><?php esc_html_e( 'Additional notes', 'wphb' ); ?></h4>
	<p>
		<?php esc_html_e( "It's not possible to change the @font-face CSS rule for web fonts hosted with most external services. You should confirm if there is a way to specify the font-display rule with your font hosting service.", 'wphb' ); ?>
	</p>
<?php endif; ?>
