<?php
/**
 * Use video formats for animated content audit.
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
	<?php esc_html_e( 'Replacing animated GIFs with videos will reduce the amount of data you send to your users and the load on your system. Converting GIFs to video files can provide a massive performance improvement with very little effort.', 'wphb' ); ?>
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
	<?php $this->admin_notices->show_inline( esc_html__( "Nice! We couldn't find any GIF on your site.", 'wphb' ) ); ?>
<?php else : ?>
	<?php
	$this->admin_notices->show_inline(
		sprintf(
			/* translators: %d - number of ms */
			esc_html__( 'You can potentially save %dms by replacing the following animated GIFs with MPEG4/WebM videos.', 'wphb' ),
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
					<td><?php echo esc_html( \Hummingbird\Core\Utils::format_bytes( $item->wastedBytes ) ); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	<?php endif; ?>

	<h4><?php esc_html_e( 'How to fix', 'wphb' ); ?></h4>
	<p><?php esc_html_e( 'Follow the steps below (if you need help, a developer can help walk you through the process.)', 'wphb' ); ?></p>
	<ol>
		<li><?php esc_html_e( 'Convert your GIFs to videos. There are CLI packages available that will do this for you or you can use a standard online GIF-to-Video converter.', 'wphb' ); ?></li>
		<li>
			<?php esc_html_e( 'Once converted, replace the <image> tag with a comparable <video> tag and configure the element to behave like a GIF. Set your video to auto-play, no sound, on a continuous loop. If the image is hardcoded in your theme template, you can replace the <image> tag with the following code:', 'wphb' ); ?>
			<pre class="sui-code-snippet sui-no-copy" style="color:#3B78E7">&lt;video <span style="color:#8D00B1 !important">autoplay loop muted</span>&gt;
	&lt;source src=<span style="color:#1ABC9C !important">“video.mp4”</span> type=<span style="color:#1ABC9C !important">“video/mp4”&gt;</span>
&lt;/video&gt;</pre>
			<p><?php esc_html_e( " Note: You'll need to upload the video to your media library and replace the source.", 'wphb' ); ?></p>
		</li>
		<li><?php esc_html_e( 'If you have your homepage set to a post or page using the block editor, delete the image block and replace it with a video block. Enable auto-play, mute the audio, and use the loop option under the block settings on the right.', 'wphb' ); ?></li>
	</ol>
<?php endif; ?>
