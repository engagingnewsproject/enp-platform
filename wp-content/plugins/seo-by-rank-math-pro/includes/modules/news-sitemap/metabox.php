<?php
/**
 * Metabox - News Sitemap
 *
 * @package    RankMath
 * @subpackage RankMath\Metaboxes
 */

use RankMath\Helper;

defined( 'ABSPATH' ) || exit;

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
