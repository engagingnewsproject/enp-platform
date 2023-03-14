<?php
/**
 * The Podcast Schema.
 *
 * @since      3.0.17
 * @package    RankMath
 * @subpackage RankMathPro\Schema
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Podcast;

use RankMath\KB;
use RankMath\Helper;
use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Arr;

defined( 'ABSPATH' ) || exit;

/**
 * Podcast class.
 */
class Podcast {

	use Hooker;

	/**
	 * The Constructor.
	 */
	public function __construct() {
		$this->filter( 'rank_math/settings/general', 'add_settings' );
		$this->action( 'init', 'init' );
		$this->action( 'rank_math/vars/register_extra_replacements', 'register_replacements' );
	}

	/**
	 * Intialize.
	 */
	public function init() {
		add_feed( 'podcast', [ $this, 'podcast_feed' ] );
		new Podcast_RSS();
		new Publish_Podcast();
	}

	/**
	 * Registers variable replacements for Rank Math Pro.
	 */
	public function register_replacements() {
		rank_math_register_var_replacement(
			'podcast_image',
			[
				'name'        => esc_html__( 'Podcast Image', 'rank-math-pro' ),
				'description' => esc_html__( 'Podcast channel image configured in the Rank Math Settings.', 'rank-math-pro' ),
				'variable'    => 'podcast_image',
				'example'     => '',
			],
			[ $this, 'get_podcast_image' ]
		);
	}

	/**
	 * Get random word from list of words. Use the object ID for the seed if persistent.
	 *
	 * @param  string $list       Words list in spintax-like format.
	 * @param  string $persistent Get persistent return value.
	 * @return string             Random word.
	 */
	public function get_podcast_image() {
		return Helper::get_settings( 'general.podcast_image' );
	}

	/**
	 * Add module settings in the General Settings panel.
	 *
	 * @param  array $tabs Array of option panel tabs.
	 * @return array
	 */
	public function add_settings( $tabs ) {
		Arr::insert(
			$tabs,
			[
				'podcast' => [
					'icon'      => 'rm-icon rm-icon-podcast',
					'title'     => esc_html__( 'Podcast', 'rank-math-pro' ),
					/* translators: Link to kb article */
					'desc'      => sprintf( esc_html__( 'Make your podcasts discoverable via Google Podcasts, Apple Podcasts, and similar services. %s.', 'rank-math' ), '<a href="' . KB::get( 'podcast-settings', 'Options Panel Podcast Tab' ) . '" target="_blank">' . esc_html__( 'Learn more', 'rank-math-pro' ) . '</a>' ),
					'file'      => dirname( __FILE__ ) . '/views/options.php',
					/* translators: Link to Podcast RSS feed */
					'after_row' => '<div class="notice notice-alt notice-info info inline rank-math-notice"><p>' . sprintf( esc_html__( 'Your Podcast RSS feed can be found here: %s', 'rank-math-pro' ), '<a href="' . Helper::get_home_url( 'feed/podcast' ) . '" target="_blank">' . Helper::get_home_url( 'feed/podcast' ) . '</a>' ) . '</p></div>',
				],
			],
			12
		);

		return $tabs;
	}

	/**
	 * Add all podcasts feed to /feed/podcast.
	 */
	public function podcast_feed() {
		require dirname( __FILE__ ) . '/views/feed-rss2.php';
	}
}
