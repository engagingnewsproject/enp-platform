<?php
/**
 * Google Trends tool for the post editor.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Admin;

use RankMath\Helper;
use RankMath\Traits\Hooker;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Trends tool class.
 *
 * @codeCoverageIgnore
 */
class Trends_Tool {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'rank_math/admin/enqueue_scripts', 'editor_scripts', 20 );
		$this->action( 'elementor/editor/before_enqueue_scripts', 'elementor_scripts' );
	}

	/**
	 * Enqueue assets for post/term/user editors.
	 *
	 * @return void
	 */
	public function editor_scripts() {
		global $pagenow;

		if ( Admin_Helper::is_post_edit() || 'term.php' === $pagenow || Admin_Helper::is_user_edit() ) {
			$editor = Helper::get_current_editor();
			$dep    = 'rank-math-metabox';

			if ( 'gutenberg' === $editor ) {
				$dep = 'rank-math-gutenberg';
			}

			if ( 'elementor' === $editor ) {
				$dep = 'rank-math-elementor';
			}

			wp_enqueue_script(
				'rank-math-pro-gutenberg',
				RANK_MATH_PRO_URL . 'assets/admin/js/gutenberg.js',
				[
					'jquery-ui-autocomplete',
					$dep,
				],
				RANK_MATH_PRO_VERSION,
				true
			);
			wp_enqueue_style( 'rank-math-pro-gutenberg', RANK_MATH_PRO_URL . 'assets/admin/css/gutenberg.css', [], RANK_MATH_PRO_VERSION );
		}
	}

	/**
	 * Add Elementor scripts.
	 *
	 * @return void
	 */
	public function elementor_scripts() {
		wp_dequeue_script( 'rank-math-pro-metabox' );
		wp_enqueue_script(
			'rank-math-pro-elementor',
			RANK_MATH_PRO_URL . 'assets/admin/js/elementor.js',
			[
				'wp-plugins',
				'rank-math-elementor',
				'jquery-ui-autocomplete',
			],
			RANK_MATH_PRO_VERSION,
			true
		);
		wp_enqueue_style( 'rank-math-pro-elementor', RANK_MATH_PRO_URL . 'assets/admin/css/elementor.css', [], RANK_MATH_PRO_VERSION );
	}
}
