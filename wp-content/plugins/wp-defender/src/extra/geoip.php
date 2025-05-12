<?php
/**
 * @package WP_Defender\Extra
 */

namespace WP_Defender\Extra;

use MaxMind\Db\Reader;

class GeoIp {
	/**
	 * @var \GeoIp2\Database\Reader
	 */
	protected $provider;

	public function __construct( $dbPath ) {
		$this->provider = new Reader( $dbPath );
	}

	/**
	 * @param $ip
	 *
	 * @return array|bool
	 * @throws Reader\InvalidDatabaseException
	 */
	public function ip_to_country( $ip ) {
		$info = $this->provider->get( $ip );
		if ( empty( $info['country'] ) ) {
			return false;
		}

		return array(
			'iso'  => $info['country']['iso_code'],
			'name' => $info['country']['names']['en'],
		);
	}
}