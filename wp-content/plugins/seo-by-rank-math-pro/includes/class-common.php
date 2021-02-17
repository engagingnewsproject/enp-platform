<?php
/**
 * Miscellaneous functions.
 *
 * @since      1.0
 * @package    RankMathPro
 * @subpackage RankMathPro\Admin
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro;

use RankMath\Traits\Hooker;
use MyThemeShop\Helpers\Url;

defined( 'ABSPATH' ) || exit;

/**
 * Admin class.
 *
 * @codeCoverageIgnore
 */
class Common {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'rank_math/admin_bar/items', 'add_admin_bar_items' );
	}

	/**
	 * Add Pinterest Rich Pins Validator to the top admin bar.
	 *
	 * @param object $object The Admin_Bar_Menu object.
	 */
	public function add_admin_bar_items( $object ) {
		$url = rawurlencode( Url::get_current_url() );
		$object->add_sub_menu(
			'rich-pins',
			[
				'title' => esc_html__( 'Rich Pins Validator', 'rank-math-pro' ),
				'href'  => 'https://developers.pinterest.com/tools/url-debugger/?link=' . $url,
				'meta'  => [
					'title'  => esc_html__( 'Pinterest Debugger', 'rank-math-pro' ),
					'target' => '_blank',
				],
			],
			'third-party'
		);
	}

}
