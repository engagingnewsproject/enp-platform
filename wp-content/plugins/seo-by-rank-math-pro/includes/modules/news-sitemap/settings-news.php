<?php
/**
 * Sitemap - News
 *
 * @since      1.0.0
 * @package    RankMath
 * @subpackage RankMathPro
 * @author     MyThemeShop <admin@mythemeshop.com>
 */

use RankMath\Helper;
use RankMath\Admin\Admin_Helper;

$cmb->add_field(
	[
		'id'   => 'news_sitemap_publication_name',
		'type' => 'text',
		'name' => esc_html__( 'Google News Publication Name', 'rank-math-pro' ),
		'desc' => wp_kses_post( __( 'The name of the news publication. It must match the name exactly as it appears on your articles in news.google.com, omitting any trailing parentheticals. <a href="https://support.google.com/news/publisher-center/answer/9606710" target="_blank">More information at support.google.com</a>', 'rank-math-pro' ) ),
	]
);

$cmb->add_field(
	[
		'id'      => 'news_sitemap_default_genres',
		'type'    => 'multicheck',
		'options' => [
			'blog'          => esc_html__( 'Blog', 'rank-math-pro' ),
			'pressrelease'  => esc_html__( 'Press Release', 'rank-math-pro' ),
			'satire'        => esc_html__( 'Satire', 'rank-math-pro' ),
			'oped'          => esc_html__( 'Op-Ed', 'rank-math-pro' ),
			'opinion'       => esc_html__( 'Opinion', 'rank-math-pro' ),
			'usergenerated' => esc_html__( 'User Generated', 'rank-math-pro' ),
		],
		'name'    => esc_html__( 'Default Genres', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Label(s) characterizing the content of the article.', 'rank-math-pro' ),
	]
);

$post_types = Helper::choices_post_types();
if ( isset( $post_types['attachment'] ) && Helper::get_settings( 'general.attachment_redirect_urls', true ) ) {
	unset( $post_types['attachment'] );
}

$cmb->add_field(
	[
		'id'      => 'news_sitemap_post_type',
		'type'    => 'multicheck_inline',
		'name'    => esc_html__( 'News Post Type', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Select the post type you use for News articles.', 'rank-math-pro' ),
		'options' => $post_types,
	]
);

$post_types = Helper::get_settings( 'sitemap.news_sitemap_post_type', [] );
if ( empty( $post_types ) ) {
	return;
}

foreach ( $post_types as $post_type ) {
	$taxonomies = Helper::get_object_taxonomies( $post_type, 'objects' );
	if ( ! empty( $taxonomies ) ) {
		foreach ( $taxonomies as $taxonomy => $data ) {
			if ( empty( $data->show_ui ) ) {
				continue;
			}

			$terms = get_terms(
				[
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'show_ui'    => true,
					'fields'     => 'id=>name',
				]
			);

			if ( count( $terms ) === 0 ) {
				continue;
			}

			$cmb->add_field(
				[
					/* translators: Taxonomy Name */
					'name'    => sprintf( esc_html__( 'Exclude %s', 'rank-math-pro' ), $data->label ),
					'id'      => "news_sitemap_exclude_{$post_type}_terms",
					'type'    => 'multicheck',
					'options' => $terms,
					/* translators: 1. Taxonomy Name 2. Post Type */
					'desc'    => sprintf( esc_html__( '%1$s to exclude for %2$s.', 'rank-math-pro' ), $data->label, $post_type ),
				]
			);
		}
	}
}
