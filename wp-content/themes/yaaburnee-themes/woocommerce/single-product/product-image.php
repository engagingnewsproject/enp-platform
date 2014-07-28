<?php
/**
 * Single Product Image
 *
 * @author 		WooThemes
 * @package 	WooCommerce/Templates
 * @version     2.0.14
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

global $post, $woocommerce, $product;

$attachment_ids = $product->get_gallery_attachment_ids();


?>
<div class="woocommerce-block bx-loading">
	<ul style="height:300px; visibility:hidden">
	<?php 
	if ( has_post_thumbnail() && $attachment_ids ) {
		$loop = 0;
		foreach ( $attachment_ids as $attachment_id ) {

			$image_link  		= wp_get_attachment_url( $attachment_id );

			$image = get_post_thumb(false, 400, 0, false, $image_link);
			$image_title = esc_attr( get_the_title( $attachment_id ) );
			$image = '<img src="'.$image["src"].'" alt="'.$image_title.'" title="'.$image_title.'" />';

			$attachment_count   = count( $product->get_gallery_attachment_ids() );

			if ( $attachment_count > 0 ) {
				$gallery = '[product-gallery]';
			} else {
				$gallery = '';
			}

			echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<li><a href="%s" itemprop="image" class="woocommerce-main-image zoom" title="%s" rel="prettyPhoto' . $gallery . '">%s</a></li>', $image_link, $image_title, $image ), $post->ID );

			$loop++;
		}
	} else {
		echo apply_filters( 'woocommerce_single_product_image_html', sprintf( '<li><img src="%s" alt="Placeholder" /></li>', woocommerce_placeholder_img_src() ), $post->ID );
	} 
?>
	</ul>
	<?php do_action( 'woocommerce_product_thumbnails' ); ?>
</div>

