<?php
/**
 * The GTag
 *
 * @since      1.4.0
 * @package    RankMath
 * @subpackage RankMath\modules
 * @author     Rank Math <support@rankmath.com>
 *
 * @credit forked from Google site kit.
 * @copyright 2019 Google LLC
 * @license   https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
 * @link      https://sitekit.withgoogle.com
 */

namespace RankMathPro\Analytics;

use RankMath\Helper;
use RankMath\Traits\Hooker;

defined( 'ABSPATH' ) || exit;

/**
 * GTag class.
 */
class GTag {

	use Hooker;

	/**
	 * Options.
	 *
	 * @var array
	 */
	private $options = null;

	/**
	 * The Constructor
	 */
	public function __construct() {
		$this->action( 'wp_head', 'print_tracking_opt_out', 0 ); // For non-AMP and AMP.
		$this->action( 'web_stories_story_head', 'print_tracking_opt_out', 0 ); // For Web Stories plugin.
	}

	/**
	 * Print the user tracking opt-out code
	 *
	 * This script opts out of all Google Analytics tracking, for all measurement IDs, regardless of implementation.
	 *
	 * @link https://developers.google.com/analytics/devguides/collection/analyticsjs/user-opt-out
	 */
	public function print_tracking_opt_out() {
		if ( ! $this->is_tracking_disabled() ) {
			return;
		}

		if ( $this->is_amp() ) :
			?>
		<script type="application/ld+json" id="__gaOptOutExtension"></script>
		<?php else : ?>
		<script type="text/javascript">window["_gaUserPrefs"] = { ioo : function() { return true; } }</script>
			<?php
		endif;
	}

	/**
	 * Is AMP url.
	 *
	 * @return bool
	 */
	protected function is_amp() {
		if ( is_singular( 'web-story' ) ) {
			return true;
		}

		return function_exists( 'is_amp_endpoint' ) && is_amp_endpoint();
	}

	/**
	 * Is tracking disabled.
	 *
	 * @return bool
	 */
	protected function is_tracking_disabled() {
		return $this->get( 'exclude_loggedin' ) && is_user_logged_in();
	}

	/**
	 * Get option
	 *
	 * @param  string $id Option to get.
	 *
	 * @return mixed
	 */
	protected function get( $id ) {
		if ( is_null( $this->options ) ) {
			$this->options = $this->normalize_it( get_option( 'rank_math_google_analytic_options', [] ) );
		}

		return isset( $this->options[ $id ] ) ? $this->options[ $id ] : false;
	}

	/**
	 * Normalize option data
	 *
	 * @param mixed $options Array to normalize.
	 * @return mixed
	 */
	protected function normalize_it( $options ) {
		foreach ( (array) $options as $key => $value ) {
			$options[ $key ] = is_array( $value ) ? $this->normalize_it( $value ) : Helper::normalize_data( $value );
		}

		return $options;
	}
}
