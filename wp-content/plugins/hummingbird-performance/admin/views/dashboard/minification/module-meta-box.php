<?php
/**
 * Asset optimization meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var float  $compressed_size          Overall compressed files size in Kb.
 * @var float  $compressed_size_scripts  Amount of space saved by compressing JavaScript.
 * @var float  $compressed_size_styles   Amount of space saved by compressing CSS.
 * @var int    $enqueued_files           Number of enqueued files.
 * @var float  $original_size            Overall original file size in Kb.
 * @var float  $percentage               Percentage saved.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<p class="sui-margin-bottom"><?php esc_html_e( 'Compress, combine and position your assets to dramatically improve your page load speed.', 'wphb' ); ?></p>

<ul class="sui-list sui-no-margin-bottom">
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Enqueued files', 'wphb' ); ?></span>
		<span class="sui-list-detail"><?php echo absint( $enqueued_files ); ?></span>
	</li>
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Total file size reductions', 'wphb' ); ?></span>
		<span class="sui-list-detail">
			<div class="wphb-pills-group">
				<span class="wphb-pills with-arrow right grey"><?php echo esc_html( $original_size ); ?>KB</span>
				<span class="wphb-pills"><?php echo esc_html( $compressed_size ); ?>KB</span>
			</div>
		</span>
	</li>
	<li>
		<span class="sui-list-label"><?php esc_html_e( 'Total % reductions', 'wphb' ); ?></span>
		<span class="sui-list-detail"><?php echo esc_html( $percentage ); ?>%</span>
	</li>
	</li>
	<li>
		<span class="sui-list-label">
			<span class="wphb-filename-extension wphb-filename-extension-js"><?php esc_html_e( 'JS', 'wphb' ); ?></span>
			<span class="wphb-filename-extension-label"><?php esc_html_e( 'JavaScript', 'wphb' ); ?></span>
		</span>
		<span class="sui-list-detail"><?php echo esc_html( $compressed_size_scripts ); ?>KB</span>
	</li>
	</li>
	<li>
		<span class="sui-list-label">
			<span class="wphb-filename-extension wphb-filename-extension-css"><?php esc_html_e( 'CSS', 'wphb' ); ?></span>
			<span class="wphb-filename-extension-label"><?php esc_html_e( 'CSS', 'wphb' ); ?></span>
		</span>
		<span class="sui-list-detail"><?php echo esc_html( $compressed_size_styles ); ?>KB</span>
	</li>
</ul>
