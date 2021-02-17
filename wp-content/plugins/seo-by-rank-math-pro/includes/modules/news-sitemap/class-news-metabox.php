<?php
/**
 * The News Sitemap Metabox.
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

namespace RankMathPro\Sitemap;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Admin\Admin_Helper;
use MyThemeShop\Helpers\WordPress;
use RankMath\Sitemap\Cache_Watcher;

defined( 'ABSPATH' ) || exit;

/**
 * News_Metabox class.
 */
class News_Metabox {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		Helper::add_json( 'addNewsTab', $this->can_add_tab() );

		$this->action( 'save_post', 'save_post' );
		$this->action( 'rank_math/metabox/tabs', 'add_tab' );
		$this->filter( 'rank_math/metabox/post/values', 'add_metadata', 10, 2 );
	}

	/**
	 * Add meta data to use in gutenberg.
	 *
	 * @param array  $values Aray of tabs.
	 * @param Screen $screen Sceen object.
	 *
	 * @return array
	 */
	public function add_metadata( $values, $screen ) {
		if ( Helper::has_cap( 'sitemap' ) ) {
			$object_id   = $screen->get_object_id();
			$object_type = $screen->get_object_type();

			$genres = $screen->get_meta( $object_type, $object_id, 'rank_math_news_sitemap_genres' );
			$genres = ! empty( $genres ) ? array_fill_keys( $genres, true ) : array_fill_keys( Helper::get_settings( 'sitemap.news_sitemap_default_genres', [] ), true );
			$robots = $screen->get_meta( $object_type, $object_id, 'rank_math_news_sitemap_robots' );

			$values['newsSitemap'] = [
				'robots'       => $robots ? $robots : 'index',
				'genres'       => $genres,
				'stockTickers' => $screen->get_meta( $object_type, $object_id, 'rank_math_news_sitemap_stock_tickers' ),
			];
		}

		return $values;
	}

	/**
	 * Add metabox tab.
	 *
	 * @param array $tabs Aray of tabs.
	 *
	 * @return array
	 */
	public function add_tab( $tabs ) {
		if ( $this->can_add_tab() ) {
			$tabs['news-tab'] = [
				'icon'       => 'rm-icon rm-icon-post',
				'title'      => esc_html__( 'News Sitemap', 'rank-math-pro' ),
				'desc'       => esc_html__( 'This tab contains news sitemap options.', 'rank-math-pro' ),
				'file'       => dirname( __FILE__ ) . '/metabox.php',
				'capability' => 'sitemap',
			];
		}

		return $tabs;
	}

	/**
	 * Check for relevant post type before invalidation.
	 *
	 * @copyright Copyright (C) 2008-2019, Yoast BV
	 * The following code is a derivative work of the code from the Yoast(https://github.com/Yoast/wordpress-seo/), which is licensed under GPL v3.
	 *
	 * @param int $post_id Post ID to possibly invalidate for.
	 */
	public function save_post( $post_id ) {
		if (
			wp_is_post_revision( $post_id ) ||
			! $this->can_add_tab( get_post_type( $post_id ) ) ||
			false === Helper::is_post_indexable( $post_id )
		) {
			return false;
		}

		Cache_Watcher::invalidate( 'news' );
	}

	/**
	 * Show field check callback.
	 *
	 * @param string $post_type Current Post Type.
	 *
	 * @return boolean
	 */
	private function can_add_tab( $post_type = false ) {
		if ( Admin_Helper::is_term_profile_page() || Admin_Helper::is_posts_page() ) {
			return false;
		}

		$post_type = $post_type ? $post_type : WordPress::get_post_type();
		return in_array(
			$post_type,
			(array) Helper::get_settings( 'sitemap.news_sitemap_post_type' ),
			true
		);
	}
}
