<?php
/**
 * Metabox - News Sitemap
 *
 * @package    RankMath
 * @subpackage RankMath\Metaboxes
 */

use RankMath\Helper;

$cmb->add_field(
	[
		'id'      => 'rank_math_news_sitemap_robots',
		'type'    => 'radio',
		'name'    => esc_html__( 'Googlebot-News index', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Using noindex allows you to prevent articles from appearing in Google News.', 'rank-math-pro' ),
		'options' => [
			'index'   => esc_html__( 'Index', 'rank-math-pro' ),
			'noindex' => esc_html__( 'No Index', 'rank-math-pro' ),
		],
		'default' => 'index',
	]
);

$cmb->add_field(
	[
		'id'      => 'rank_math_news_sitemap_genres',
		'type'    => 'multicheck',
		'options' => [
			'blog'          => esc_html__( 'Blog', 'rank-math-pro' ),
			'pressrelease'  => esc_html__( 'Press Release', 'rank-math-pro' ),
			'satire'        => esc_html__( 'Satire', 'rank-math-pro' ),
			'oped'          => esc_html__( 'Op-Ed', 'rank-math-pro' ),
			'opinion'       => esc_html__( 'Opinion', 'rank-math-pro' ),
			'usergenerated' => esc_html__( 'User Generated', 'rank-math-pro' ),
		],
		'name'    => esc_html__( 'Genres', 'rank-math-pro' ),
		'desc'    => esc_html__( 'Label(s) characterizing the content of the article.', 'rank-math-pro' ),
		'default' => Helper::get_settings( 'sitemap.news_sitemap_default_genres', [] ),
	]
);

$cmb->add_field(
	[
		'id'   => 'rank_math_news_sitemap_stock_tickers',
		'type' => 'text',
		'name' => esc_html__( 'Stock Tickers', 'rank-math-pro' ),
		'desc' => esc_html__( 'A comma-separated list of up to 5 stock tickers of the companies, mutual funds, or other financial entities that are the main subject of the article. Relevant primarily for business articles.', 'rank-math-pro' ),
	]
);
