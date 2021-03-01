<?php
/**
 * Metabox - Compare keywords
 *
 * @package    RankMathPro
 * @subpackage RankMathPro\Metaboxes
 */

defined( 'ABSPATH' ) || exit;

?>
<div id="rank-math-compare-keywords-wrapper" class="rank-math-compare-keywords-wrapper" style="position: relative;display:none">

	<div class="media-modal wp-core-ui">

		<button type="button" class="media-modal-close"><span class="media-modal-icon"><span class="screen-reader-text"><?php esc_html_e( 'Close panel', 'rank-math-pro' ); ?></span></span></button>

		<div class="media-modal-content">

			<div class="media-frame mode-select wp-core-ui hide-toolbar hide-router hide-sidebar">

				<div class="media-frame-menu">
					<div class="rank-math-compare-keywords-menu">
						<h1 class="rank-math-compare-keywords-header"><?php esc_html_e( 'Keywords', 'rank-math-pro' ); ?></h1>
						<div class="keyword-input">
							<input type="text" class="widefat add-compare-keyword" value="" placeholder="<?php echo esc_attr__( 'Enter Keyword', 'rank-math-pro' ); ?>">
							<button type="button" class="button button-secondary add-new-keywords"><span class="dashicons dashicons-plus"></span> <?php esc_html_e( 'Add', 'rank-math-pro' ); ?></button>
						</div>
						<div class="rank-math-keywords-fields"></div>
						<div class="aligncenter">
							<button type="button" class="button button-secondary compare-keywords button-actions hidden"><?php esc_html_e( 'Compare Keywords', 'rank-math-pro' ); ?></button>
							<a href="#" class="use-these-keywords button button-primary button-actions hidden"><?php esc_html_e( 'Close and use selected keywords', 'rank-math-pro' ); ?></a>
							<a href="#" class="close-popup button button-secondary button-actions"><?php esc_html_e( 'Cancel', 'rank-math-pro' ); ?></a>
						</div>

						<div class="separator"></div>

						<p class="source-credit">
							<span><?php esc_html_e( 'Data source: Google Trends', 'rank-math-pro' ); ?></span>
						</p>
					</div>
				</div>

				<div class="media-frame-title"><h1><?php esc_html_e( 'Trends', 'rank-math-pro' ); ?></h1></div>

				<div class="media-frame-content">
					<div class="attachments-browser">
						<iframe class="attachments-browser hidden" data-src="https://trends.google.com:443/trends/embed/explore/TIMESERIES?tz=-60&hl=en"></iframe>
					</div>
				</div>

			</div>

		</div>

	</div>

	<div class="media-modal-backdrop"></div>

</div>
