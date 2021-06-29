<?php
/**
 *  Google AdSense.
 *
 * @since      1.0.34
 * @package    RankMathPro
 * @subpackage RankMathPro\modules
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMathPro\Google;

use RankMath\Google\Api;
use RankMath\Helpers\Security;

defined( 'ABSPATH' ) || exit;

/**
 * AdSense class.
 */
class Adsense {

	/**
	 * Get adsense accounts.
	 *
	 * @return array
	 */
	public static function get_adsense_accounts() {
		$accounts = [];
		$response = Api::get()->http_get( 'https://www.googleapis.com/adsense/v1.4/accounts' );
		if ( ! Api::get()->is_success() || isset( $response->error ) ) {
			return $accounts;
		}

		foreach ( $response['items'] as $account ) {
			if ( 'adsense#account' !== $account['kind'] ) {
				continue;
			}

			$accounts[ $account['id'] ] = [
				'name' => $account['name'],
			];
		}

		return $accounts;
	}

	/**
	 * Query adsense data from google client api.
	 *
	 * @param string $start_date Start date.
	 * @param string $end_date   End date.
	 *
	 * @return array
	 */
	public static function get_adsense( $start_date, $end_date ) {
		if ( ! self::get_adsense_id() ) {
			return false;
		}

		$request  = Security::add_query_arg_raw(
			[
				'accountId' => self::get_adsense_id(),
				'startDate' => $start_date,
				'endDate'   => $end_date,
				'dimension' => 'DATE',
				'metric'    => 'EARNINGS',
			],
			'https://www.googleapis.com/adsense/v1.4/reports'
		);
		$response = Api::get()->http_get( $request );

		Api::get()->log_failed_request( $response, 'adsense', $start_date, func_get_args() );

		if ( ! Api::get()->is_success() || ! isset( $response['rows'] ) ) {
			return false;
		}

		return $response['rows'];
	}

	/**
	 * Get adsense id.
	 *
	 * @return string
	 */
	public static function get_adsense_id() {
		static $rank_math_adsense_id;

		if ( is_null( $rank_math_adsense_id ) ) {
			$options              = get_option( 'rank_math_google_analytic_options' );
			$rank_math_adsense_id = ! empty( $options['adsense_id'] ) ? $options['adsense_id'] : false;
		}

		return $rank_math_adsense_id;
	}

	/**
	 * Is adsense connected.
	 *
	 * @return boolean
	 */
	public static function is_adsense_connected() {
		$account = wp_parse_args(
			get_option( 'rank_math_google_analytic_options' ),
			[ 'adsense_id' => '' ]
		);

		return ! empty( $account['adsense_id'] );
	}
}
