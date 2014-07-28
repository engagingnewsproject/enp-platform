<?php
	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/* -------------------------------------------------------------------------*
 * 								HOMEPAGE BUILDER							*
 * -------------------------------------------------------------------------*/
 
class DF_home_builder {

	private static $data;
	public static $counter = 1; 


	/* -------------------------------------------------------------------------*
	 * 					HOMEPAGE LATEST & CATEGORY ARTICLES						*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$style = get_option(THEME_NAME."_".$blockType."_style_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);

		if(!$offset) {
			$offset = "0";
		}
		if($cat) {
			$pageColor = df_title_color($cat, "category", false);
			$link = get_category_link($cat);
		} else {
			$pageColor = df_title_color(get_option('page_for_posts'),'page', false);
			$link = get_page_link(get_option('page_for_posts'));
		}
		
		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count,
			'pageColor' =>$pageColor,
			'link' =>$link

		);

		//set wp query
		$args = array(
			'post_type' => "post",
			'cat' => $cat,
			'showposts' => $count,
			'ignore_sticky_posts' => "1",
			'offset' =>$offset
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "block-1-style-".$style;
		return $block;

	}

	/* -------------------------------------------------------------------------*
	 * 				HOMEPAGE LATEST & CATEGORY ARTICLES	TOUCHCAROUSEL			*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_2($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);

		if(!$offset) {
			$offset = "0";
		}

		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count,
		);

		//set wp query
		$args = array(
			'post_type' => "post",
			'cat' => $cat,
			'showposts' => $count,
			'ignore_sticky_posts' => "1",
			'offset' =>$offset
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "block-2";
		return $block;

	}

	/* -------------------------------------------------------------------------*
	 * 				HOMEPAGE LATEST & CATEGORY ARTICLES - BLOG STYLE			*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_3($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$style = get_option(THEME_NAME."_".$blockType."_style_".$blockId);
		$pagination = get_option(THEME_NAME."_".$blockType."_pagination_".$blockId);

		if($cat) {
			$pageColor = df_title_color($cat, "category", false);
			$link = get_category_link($cat);
		} else {
			$pageColor = df_title_color(get_option('page_for_posts'),'page', false);
			$link = get_page_link(get_option('page_for_posts'));
		}

   		$paged = get_query_string_paged();

		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count,
			'pageColor' =>$pageColor,
			'link' =>$link,
			'style' =>$style,
			'paged' =>$paged,
			'pagination' =>$pagination
		);

		//set wp query
		$args = array(
			'post_type' => "post",
			'cat' => $cat,
			'paged'=>$paged,
			'posts_per_page' => $count,
			'ignore_sticky_posts' => "1"
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "block-3";
		return $block;


	}
	/* -------------------------------------------------------------------------*
	 * 							HOMEPAGE POPULAR ARTICLES						*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_4($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$style = get_option(THEME_NAME."_".$blockType."_style_".$blockId);
		$color = get_option(THEME_NAME."_".$blockType."_color_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);
		
		if(!$offset) {
			$offset = "0";
		}

		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count,
			'pageColor' =>"#".$color,
			'link' =>false
		);

		//set wp query
		$args = array(
			'post_type' => "post",
			'cat' => $cat,
			'posts_per_page' => $count,
			'ignore_sticky_posts' => "1",
			'orderby'	=> 'meta_value_num',
			'order' => 'DESC',
			'meta_key'	=> THEME_NAME.'_post_views_count',
			'offset' =>$offset
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "block-1-style-".$style;
		return $block;


	}

	/* -------------------------------------------------------------------------*
	 * 					HOMEPAGE POPULAR ARTICLES TOUCHCAROUSEL					*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_5($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$style = get_option(THEME_NAME."_".$blockType."_style_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);

		if(!$offset) {
			$offset = "0";
		}

		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count
		);

		//set wp query
		$args = array(
			'post_type' => "post",
			'cat' => $cat,
			'posts_per_page' => $count,
			'ignore_sticky_posts' => "1",
			'orderby'	=> 'meta_value_num',
			'order' => 'DESC',
			'meta_key'	=> THEME_NAME.'_post_views_count',
			'offset' =>$offset
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "block-2";
		return $block;


	}


	/* -------------------------------------------------------------------------*
	 * 					HOMEPAGE LATEST & CATEGORY REVIEWS						*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_6($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$style = get_option(THEME_NAME."_".$blockType."_style_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);

		if(!$offset) {
			$offset = "0";
		}

		if($cat) {
			$pageColor = df_title_color($cat, "category", false);
		} else {
			$pageColor = df_title_color(get_option('page_for_posts'),'page', false);
		}
		
		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count,
			'pageColor' =>$pageColor,
			'link' =>false
		);

		//set wp query
		$args = array(
			'post_type' => "post",
			'cat' => $cat,
			'order' => 'DESC',
			'showposts' => $count,
			'ignore_sticky_posts' => "1",
			'meta_query' => array(
			    array(
			        'key' => THEME_NAME.'_rating',
			        'value'   => '0',
			        'compare' => '>='
			    )
			),
			'offset' =>$offset
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "block-2";
		return $block;

	}

	/* -------------------------------------------------------------------------*
	 * 			HOMEPAGE LATEST & CATEGORY REVIEWS - TOUCHCAROUSEL				*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_7($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);

		if(!$offset) {
			$offset = "0";
		}
		
		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count,
		);

		//set wp query
		$args = array(
			'post_type' => "post",
			'cat' => $cat,
			'order' => 'DESC',
			'showposts' => $count,
			'ignore_sticky_posts' => "1",
			'meta_query' => array(
			    array(
			        'key' => THEME_NAME.'_rating',
			        'value'   => '0',
			        'compare' => '>='
			    )
			),
			'offset' =>$offset
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "block-2";
		return $block;

	}

	/* -------------------------------------------------------------------------*
	 * 							HOMEPAGE FEATURED SHOP ITEMS					*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_8($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);
		$color = get_option(THEME_NAME."_".$blockType."_color_".$blockId);

		if(!$offset) {
			$offset = "0";
		}

		
		//set block attributes
		$attr = array(
			'title' =>$title,
			'count' =>$count,
			'pageColor' => "#".$color,
			'link' => false
		);

		//set wp query
		$args = array(
			'post_type' => "product",
			'showposts' => $count,
			'ignore_sticky_posts' => "1",
			'offset' =>$offset,
			'post_status '	=> 'publish',
			'meta_key'	=> '_featured',
			'meta_value'	=> 'yes',
		);
		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "shop-items";
		return $block;

	}

	/* -------------------------------------------------------------------------*
	 * 					HOMEPAGE LATEST SHOP ITEMS & CATEGORY ITEMS				*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_news_block_9($blockType, $blockId,$blockInputType) {
		global $post;
		$title = get_option(THEME_NAME."_".$blockType."_title_".$blockId);
		$cat = (int)get_option(THEME_NAME."_".$blockType."_cat_".$blockId);
		$count = get_option(THEME_NAME."_".$blockType."_count_".$blockId);
		$offset = get_option(THEME_NAME."_".$blockType."_offset_".$blockId);
		$color = get_option(THEME_NAME."_".$blockType."_color_".$blockId);

		if(!$offset) {
			$offset = "0";
		}

		if($cat) {
			$link = get_term_link($cat, 'product_cat');
		} else {
			if(df_is_woocommerce_activated()==true) {
				$link = get_page_link(woocommerce_get_page_id('shop'));
			} else {
				$link = false;
			}
		}
		
		//set block attributes
		$attr = array(
			'title' =>$title,
			'cat' =>$cat,
			'count' =>$count,
			'pageColor' => "#".$color,
			'link' => $link

		);

		if($cat) {
			//set wp query
			$args = array(
				'post_type' => "product",
				'tax_query' => array(
					array(
						'taxonomy' => 'product_cat',
						'field' => 'id',
						'terms' => $cat
					)
				),
				'showposts' => $count,
				'ignore_sticky_posts' => "1",
				'offset' =>$offset
			);
		} else {
			//set wp query
			$args = array(
				'post_type' => "product",
				'showposts' => $count,
				'ignore_sticky_posts' => "1",
				'offset' =>$offset
			);
		}


		$my_query = new WP_Query($args);

		//add all data in array
		$data = array($my_query, $attr);

		//set data
		$this->set_data($data);
		$block = "shop-items";
		return $block;

	}

	/* -------------------------------------------------------------------------*
	 * 								HOMEPAGE BANNER								*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_banner($blockType, $blockId,$blockInputType) {
		global $post;
		$code = get_option(THEME_NAME."_".$blockType."_".$blockId);

		
		//set block attributes
		$attr = array(
			'code' =>stripslashes(do_shortcode($code)),
		);


		//add all data in array
		$data = array($attr);

		//set data
		$this->set_data($data);
		$block = "block-4";
		return $block;

	}	

	/* -------------------------------------------------------------------------*
	 * 							HOMEPAGE HTML BLOCK								*
	 * -------------------------------------------------------------------------*/
	 
	public function homepage_html($blockType, $blockId,$blockInputType) {
		global $post;
		$code = get_option(THEME_NAME."_".$blockType."_".$blockId);

		
		//set block attributes
		$attr = array(
			'code' =>stripslashes(do_shortcode($code)),
		);


		//add all data in array
		$data = array($attr);

		//set data
		$this->set_data($data);
		$block = "block-5";
		return $block;

	}

	private static function set_data($data) {
		self::$data = $data;
	}

	public static function get_data() {
		return self::$data;
	}


} 
?>