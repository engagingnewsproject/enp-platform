<?php
/**
 * Miscellaneous admin related functionality.
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
 * Misc admin class.
 *
 * @codeCoverageIgnore
 */
class Misc {

	use Hooker;

	/**
	 * Register hooks.
	 */
	public function __construct() {
		$this->action( 'cmb2_default_filter', 'change_fk_default', 20, 2 );
	}

	/**
	 * Add options to Image SEO module.
	 *
	 * @param mixed  $default Default value.
	 * @param object $field   Field object.
	 */
	public function change_fk_default( $default, $field ) {
		if ( 'rank_math_focus_keyword' !== $field->id() ) {
			return $default;
		}

		if ( ! Admin_Helper::is_term_edit() ) {
			return $default;
		}

		return $this->get_term();
	}

	/**
	 * Get term.
	 *
	 * @return string
	 */
	public function get_term() {
		global $tag;
		if ( isset( $tag->name ) ) {
			return $tag->name;
		}

		return '';
	}
}
