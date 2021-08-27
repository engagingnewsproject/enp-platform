<?php
/**
 *
 * Comment template substitute when lazy load is enabled. Displays button or scroll div.
 *
 * @package Hummingbird\Core
 */

$options = get_query_var( 'lazy_load_settings' );
if ( empty( $options ) ) {
	return;
}

// Get current comments page number.
$cpage = get_query_var( 'cpage' );
if ( ! $cpage ) {
	$cpage = 1;
}

$method = $options['method'];
if ( empty( $method ) ) {
	$method = 'click';
}

$page_comments = (int) get_option( 'page_comments' );
if ( 0 === $page_comments ) {
	/**
	 * Override page_comments decision set from discussion settings page.
	 * By default get_comment_pages_count returns 1 when page_comments is false.
	 * See in function get_comment_pages_count() in wp-includes/comment.php
	 * We need it always true to get comment pages count.
	 */
	add_filter( 'option_page_comments', '__return_true', 100 );
}

$total_comment_pages = get_comment_pages_count();
if ( 0 === $page_comments ) {
	remove_filter( 'option_page_comments', '__return_true' );
}

$comments_page_order = get_option( 'default_comments_page' ); // Possible values : newest, oldest.
if ( 0 === $page_comments && 'newest' === $comments_page_order && 1 === $cpage ) {
	$cpage = $total_comment_pages;
}
?>
<div id='wphb-comments-wrap'>
	<div id="wphb-comments-container"><!-- Comments will be loaded here --></div>
	<?php
	if ( 'click' === $method ) {
		$height = $options['button']['dimensions']['height'] ? 'height: ' . $options['button']['dimensions']['height'] . 'px;' : '';
		$width  = $options['button']['dimensions']['width'] ? 'width: ' . $options['button']['dimensions']['width'] . 'px;' : '';
		$radius = $options['button']['dimensions']['radius'] ? 'border-radius: ' . $options['button']['dimensions']['radius'] . 'px;' : '';

		$background   = $options['button']['color']['background'] ? 'background-color: ' . $options['button']['color']['background'] . ';' : '';
		$border_color = $options['button']['color']['border'] ? 'border-color: ' . $options['button']['color']['border'] . ';' : '';
		$hover        = $options['button']['color']['hover'] ? 'background-color: ' . $options['button']['color']['hover'] . ';' : '';

		$left   = isset( $options['button']['alignment']['left'] ) ? 'margin-left: ' . $options['button']['alignment']['left'] . 'px;' : '';
		$right  = isset( $options['button']['alignment']['right'] ) ? 'margin-right: ' . $options['button']['alignment']['right'] . 'px;' : '';
		$top    = isset( $options['button']['alignment']['top'] ) ? 'margin-top: ' . $options['button']['alignment']['top'] . 'px;' : '';
		$bottom = isset( $options['button']['alignment']['bottom'] ) ? 'margin-bottom: ' . $options['button']['alignment']['bottom'] . 'px;' : '';
		?>
		<style>
			.wphb-load-comments-wrap {
				text-align: <?php echo esc_attr( $options['button']['alignment']['align'] ); ?>;
			}
			button#wphb-load-comments {
			<?php
				echo esc_attr( $background );
				echo esc_attr( $border_color );
				echo esc_attr( $height );
				echo esc_attr( $width );
				echo esc_attr( $radius );
				echo esc_attr( $left );
				echo esc_attr( $right );
				echo esc_attr( $bottom );
				echo esc_attr( $top );
			?>
			}
			button#wphb-load-comments:hover:enabled {
			<?php
				echo esc_attr( $hover );
			?>
			}
			button#wphb-load-comments:hover:disabled {
				cursor: initial;
				text-decoration: none;
			}
			button#wphb-load-comments:disabled {
				background: #cccccc;
			}
		</style>
		<div id="wphb-load-comments-button-wrap" class='wphb-load-comments-wrap'>
			<button type='button' id='wphb-load-comments' disabled="disabled"><?php esc_html_e( 'Load comments', 'wphb' ); ?></button>
		</div>
		<?php
	}
	?>
	<style type="text/css">
		#wphb-load-comments-spinner-wrap{
			text-align: center;
			margin: 10px auto;
			display:none;
		}
	</style>
	<div id="wphb-load-comments-spinner-wrap" class='wphb-load-comments-spinner-wrap'>
		<?php esc_html_e( 'Loading comments', 'wphb' ); ?>...
	</div>
	<input type="hidden" id="wphb-load-comments-method" name="wphb-load-comments-method" value="<?php echo esc_attr( $method ); ?>"/>
	<input type="hidden" id="wphb-page-comments-option" name="wphb-page-comments-option" value="<?php echo esc_attr( $page_comments ); ?>"/>
	<input type="hidden" id="wphb-total-comments-pages" name="wphb-total-comments-pages" value="<?php echo esc_attr( $total_comment_pages ); ?>"/>
	<input type="hidden" id="wphb-comments-page-order" name="wphb-comments-page-order" value="<?php echo esc_attr( $comments_page_order ); ?>"/>

	<input type="hidden" id="wphb-post-id" name="post-id" value="<?php echo get_the_ID(); ?>"/>
	<input type="hidden" id="wphb-cpage-num" name="wphb-cpage" value="<?php echo esc_attr( $cpage ); ?>">
	<?php
		wp_nonce_field( 'comments_template', 'comment-template-nonce' );
	?>
	<div id="wphb-comments-end-indicator"></div>
</div>
