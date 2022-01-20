<?php

namespace WP_Defender\Component;

use WP_Defender\Component;

class Blacklist_Lockout extends Component {
	use \WP_Defender\Traits\Country;
	use \WP_Defender\Traits\IP;

	/**
	 * Queue hooks when this class init.
	 */
	public function add_hooks() {
		add_filter( 'defender_ip_lockout_assets', array( &$this, 'output_scripts_data' ) );
	}

	/**
	 * @param $data
	 *
	 * @return mixed
	 * @throws \MaxMind\Db\Reader\InvalidDatabaseException
	 */
	public function output_scripts_data( $data ) {
		$model             = new \WP_Defender\Model\Setting\Blacklist_Lockout();
		$current_country   = $this->get_current_country();
		$data['blacklist'] = array(
			'model'   => $model->export(),
			'summary' => array(
				'day' => 0,
			),
			'misc'    => array(
				'geo_db_downloaded'   => $model->is_geodb_downloaded(),
				'current_country'     => isset( $current_country['iso'] ) ? $current_country['iso'] : null,
				'blacklist_countries' => array_merge(
					array( 'all' => __( 'Block all', 'wpdef' ) ),
					$this->countries_list()
				),
				'whitelist_countries' => array_merge(
					array( 'all' => __( 'Allow all', 'wpdef' ) ),
					$this->countries_list()
				),
				'geo_requirement'     => version_compare( phpversion(), WP_DEFENDER_MIN_PHP_VERSION, '>=' ),
				'user_ip'             => $this->get_user_ip(),
			),
			'class'   => \WP_Defender\Model\Setting\Blacklist_Lockout::class,
		);

		return $data;
	}

	/**
	 * @return array|bool
	 * @throws \MaxMind\Db\Reader\InvalidDatabaseException
	 */
	public function get_current_country() {
		if ( 'cli' === php_sapi_name() ) {
			// Never catch if from cli.
			return false;
		}

		$model = new \WP_Defender\Model\Setting\Blacklist_Lockout();
		if ( ! $model->is_geodb_downloaded() ) {
			return false;
		}

		$geo_ip = new \WP_Defender\Extra\GeoIp( $model->geodb_path );
		$ip     = $this->get_user_ip();
		if ( ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return false;
		}

		return $geo_ip->ip_to_country( $ip );
	}

	/**
	 * @return bool
	 */
	public function is_country_whitelist() {
		$model     = new \WP_Defender\Model\Setting\Blacklist_Lockout();
		$whitelist = $model->get_country_whitelist();
		if ( empty( $whitelist ) ) {
			return false;
		}

		$country = $this->get_current_country();
		if ( ! empty( $country['iso'] ) && in_array( strtoupper( $country['iso'] ), $whitelist, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Return the default ips need to whitelisted, e.g. HUB ips.
	 *
	 * @return array
	 */
	private function get_default_ip_whitelisted() {
		$ips = array(
			'192.241.148.185',
			'104.236.132.222',
			'192.241.140.159',
			'192.241.228.89',
			'198.199.88.192',
			'54.197.28.242',
			'54.221.174.186',
			'54.236.233.244',
			'18.204.159.253',
			'66.135.60.59',
			'34.196.51.17',
			'52.57.5.20',
			'127.0.0.1',
			array_key_exists( 'SERVER_ADDR', $_SERVER )
				? $_SERVER['SERVER_ADDR']
				: ( isset( $_SERVER['LOCAL_ADDR'] ) ? $_SERVER['LOCAL_ADDR'] : null ),
		);

		return apply_filters( 'ip_lockout_default_whitelist_ip', $ips );
	}

	/**
	 * @param string $ip
	 * @param array $arr_ips
	 *
	 * @return bool
	 */
	private function is_ip_in_format( $ip, $arr_ips ) {
		foreach ( $arr_ips as $wip ) {
			if ( false === strpos( $wip, '-' ) && false === strpos( $wip, '/' ) && trim( $wip ) === $ip ) {
				return true;
			} elseif ( false !== strpos( $wip, '-' ) ) {
				$ips = explode( '-', $wip );
				if ( $this->compare_in_range( $ip, $ips[0], $ips[1] ) ) {
					return true;
				}
			} elseif ( false !== strpos( $wip, '/' ) && $this->compare_cidr( $ip, $wip ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Is IP on Whitelist?
	 * @param string $ip
	 *
	 * @return bool
	 */
	public function is_ip_whitelisted( $ip ) {
		if ( in_array( $ip, $this->get_default_ip_whitelisted(), true ) ) {
			return true;
		}

		$blacklist_settings = new \WP_Defender\Model\Setting\Blacklist_Lockout();

		return $this->is_ip_in_format( $ip, $blacklist_settings->get_list( 'allowlist' ) );
	}

	/**
	 * Is IP on Blocklist?
	 *
	 * @param string $ip
	 *
	 * @return bool
	 */
	public function is_blacklist( $ip ) {
		$blacklist_settings = new \WP_Defender\Model\Setting\Blacklist_Lockout();

		return $this->is_ip_in_format( $ip, $blacklist_settings->get_list( 'blocklist' ) );
	}

	/**
	 * Is country on Blacklist?
	 *
	 * @return bool
	 */
	public function is_country_blacklist() {
		// Return if php less than 5.6.20.
		if ( version_compare( phpversion(), WP_DEFENDER_MIN_PHP_VERSION, '<' ) ) {
			return false;
		}
		$country = $this->get_current_country();

		if ( false === $country ) {
			return false;
		}
		// If this country is whitelisted, so we don't need to blacklist this.
		if ( $this->is_country_whitelist() ) {
			return false;
		}

		$blacklist_settings = new \WP_Defender\Model\Setting\Blacklist_Lockout();
		$blacklisted        = $blacklist_settings->get_country_blacklist();

		if ( empty( $blacklisted ) ) {
			return false;
		}
		if ( in_array( 'all', $blacklisted, true ) ) {
			return true;
		}
		if ( in_array( strtoupper( $country['iso'] ), $blacklisted, true ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Validate import file is in right format and usable for IP Lockout.
	 *
	 * @param $file
	 *
	 * @return array|bool
	 */
	public function verify_import_file( $file ) {
		$fp   = fopen( $file, 'r' );
		$data = array();
		while ( ( $line = fgetcsv( $fp ) ) !== false ) { //phpcs:ignore
			if ( 2 !== count( $line ) ) {
				return false;
			}

			if ( ! in_array( $line[1], array( 'allowlist', 'blocklist' ), true ) ) {
				return false;
			}

			if ( false === $this->validate_ip( $line[0] ) ) {
				continue;
			}

			$data[] = $line;
		}
		fclose( $fp );

		return $data;
	}

	/**
	 * Like download_geodb.
	 */
	public function download_geo_ip() {
		$url = 'http://geolite.maxmind.com/download/geoip/database/GeoLite2-Country.tar.gz';
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		$this->download_by_url( $url );
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public function download_by_url( $url ) {
		$tmp = download_url( $url );
		if ( ! is_wp_error( $tmp ) ) {
			$phar = new \PharData( $tmp );
			$path = $this->get_tmp_path() . DIRECTORY_SEPARATOR . 'maxmind';
			if ( ! is_dir( $path ) ) {
				wp_mkdir_p( $path );
			}
			$phar->extractTo( $path, null, true );
			$model             = new \WP_Defender\Model\Setting\Blacklist_Lockout();
			$model->geodb_path = $path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . 'GeoLite2-Country.mmdb';
			if ( empty( $model->country_whitelist ) ) {
				$country = $this->get_current_country( $this->get_user_ip() );

				if ( ! empty( $country['iso'] ) ) {
					$model->country_whitelist[] = $country['iso'];
				}
			}
			$model->save();

			return true;
		}

		return false;
	}
}
