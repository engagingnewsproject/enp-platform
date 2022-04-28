<?php

namespace WP_Defender\Component;

use WP_Defender\Component;
use WP_Defender\Integrations\MaxMind_Geolocation;
use WP_Defender\Model\Setting\Blacklist_Lockout as Model_Blacklist_Lockout;

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
	 * @param array $data
	 *
	 * @return mixed
	 * @throws \MaxMind\Db\Reader\InvalidDatabaseException
	 */
	public function output_scripts_data( $data ) {
		$model       = new Model_Blacklist_Lockout();
		$user_ip     = $this->get_user_ip();
		$exist_geodb = $this->is_geodb_downloaded();
		// If MaxMind GeoIP DB is downloaded then display the required data.
		if ( $exist_geodb ) {
			$current_country     = $this->get_current_country( $user_ip );
			$current_country     = isset( $current_country['iso'] ) ? $current_country['iso'] : false;
			$country_list        = $this->countries_list();
			$blacklist_countries = array_merge( array( 'all' => __( 'Block all', 'wpdef' ) ), $country_list );
			$whitelist_countries = array_merge( array( 'all' => __( 'Allow all', 'wpdef' ) ), $country_list );
		} else {
			$current_country     = false;
			$blacklist_countries = array();
			$whitelist_countries = array();
		}
		$data['blacklist'] = array(
			'model'   => $model->export(),
			'summary' => array(
				'day' => 0,
			),
			'misc'    => array(
				'geo_db_downloaded'   => $exist_geodb,
				'current_country'     => $current_country,
				'blacklist_countries' => $blacklist_countries,
				'whitelist_countries' => $whitelist_countries,
				'geo_requirement'     => version_compare( phpversion(), WP_DEFENDER_MIN_PHP_VERSION, '>=' ),
				'user_ip'             => $user_ip,
			),
			'class'   => Model_Blacklist_Lockout::class,
		);

		return $data;
	}

	/**
	 * @param string $ip
	 *
	 * @return bool
	 */
	public function is_country_whitelist( $ip ) {
		// Check Firewall > IP Banning > Locations section is activated or not.
		$country = $this->get_current_country( $ip );
		if ( false === $country ) {
			return false;
		}
		$model     = new Model_Blacklist_Lockout();
		$whitelist = $model->get_country_whitelist();
		if ( empty( $whitelist ) ) {
			return false;
		}
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
	 * @param array  $arr_ips
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
	 *
	 * @param string $ip
	 *
	 * @return bool
	 */
	public function is_ip_whitelisted( $ip ) {
		if ( in_array( $ip, $this->get_default_ip_whitelisted(), true ) ) {
			return true;
		}

		$blacklist_settings = new Model_Blacklist_Lockout();

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
		$blacklist_settings = new Model_Blacklist_Lockout();

		return $this->is_ip_in_format( $ip, $blacklist_settings->get_list( 'blocklist' ) );
	}

	/**
	 * Is country on Blacklist?
	 *
	 * @param string $ip
	 *
	 * @return bool
	 */
	public function is_country_blacklist( $ip ) {
		// Check Firewall > IP Banning > Locations section is activated or not.
		$country = $this->get_current_country( $ip );
		if ( false === $country ) {
			return false;
		}
		$blacklist_settings = new Model_Blacklist_Lockout();
		$blacklisted        = $blacklist_settings->get_country_blacklist();
		if ( empty( $blacklisted ) ) {
			return false;
		}
		if ( in_array( 'all', $blacklisted, true ) ) {
			return true;
		}
		if ( ! empty( $country['iso'] ) && in_array( strtoupper( $country['iso'] ), $blacklisted, true ) ) {
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
	 * @param Model_Blacklist_Lockout $model
	 * @param string                  $country_iso
	 *
	 * @return object
	 * @since 2.8.0
	*/
	public function add_default_whitelisted_country( Model_Blacklist_Lockout $model, $country_iso ) {
		if ( empty( $model->country_whitelist ) ) {
			$model->country_whitelist[] = $country_iso;
		} elseif ( ! in_array( $country_iso, $model->country_whitelist, true ) ) {
			$model->country_whitelist[] = $country_iso;
		}

		return $model;
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
	 *
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
			$model             = new Model_Blacklist_Lockout();
			$service_geo       = wd_di()->get( MaxMind_Geolocation::class );
			$model->geodb_path = $path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . $service_geo->get_db_full_name();

			if ( file_exists( $tmp ) ) {
				unlink( $tmp );
			}

			if ( empty( $model->country_whitelist ) ) {
				$country = $this->get_current_country( $this->get_user_ip() );
				if ( false === $country ) {
					return false;
				}
				if ( ! empty( $country['iso'] ) ) {
					$model = $this->add_default_whitelisted_country( $model, $country['iso'] );
				}
			}
			$model->save();

			return true;
		}

		return false;
	}

	/**
	 * @param string $license_key
	 *
	 * @return bool|string|\WP_Error
	 */
	public function get_maxmind_downloaded_url( $license_key ) {
		$url = "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-Country&license_key=$license_key&suffix=tar.gz";
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		return download_url( $url );
	}

	/**
	 * Check downloaded GeoDB.
	 *
	 * @return bool
	 */
	public function is_geodb_downloaded() {
		$model = new Model_Blacklist_Lockout();
		// Likely the case after the config import with existed MaxMind license key.
		if (
			! empty( $model->maxmind_license_key )
			&& ( is_null( $model->geodb_path ) || ! is_file( $model->geodb_path ) )
		) {
			$service_geo = wd_di()->get( MaxMind_Geolocation::class );
			$tmp         = $service_geo->get_downloaded_url( $model->maxmind_license_key );
			if ( ! is_wp_error( $tmp ) ) {
				$phar = new \PharData( $tmp );
				$path = $this->get_tmp_path() . DIRECTORY_SEPARATOR . 'maxmind';
				if ( ! is_dir( $path ) ) {
					wp_mkdir_p( $path );
				}
				$phar->extractTo( $path, null, true );
				$model->geodb_path = $path . DIRECTORY_SEPARATOR . $phar->current()->getFileName() . DIRECTORY_SEPARATOR . $service_geo->get_db_full_name();
				// Save because we'll check for a saved path.
				$model->save();

				if ( file_exists( $tmp ) ) {
					unlink( $tmp );
				}

				if ( empty( $model->country_whitelist ) ) {
					$country = $this->get_current_country( $this->get_user_ip() );
					if ( false === $country ) {
						return false;
					}
					if ( ! empty( $country['iso'] ) ) {
						$model = $this->add_default_whitelisted_country( $model, $country['iso'] );
					}
				}
				$model->save();
			}
		}

		// Check again.
		if ( is_null( $model->geodb_path ) || ! is_file( $model->geodb_path ) ) {
			return false;
		}

		// Check if the file exists on the site. The file can exist on the same server but for different sites.
		// For example, after config importing.
		$path_parts = pathinfo( $model->geodb_path );
		if ( preg_match( '/(\/wp-content\/.+)/', $path_parts['dirname'], $matches ) ) {
			$rel_path = $matches[1];
			$rel_path = ltrim( $rel_path, '/' );
			$abs_path = ABSPATH . $rel_path;
			if ( ! is_dir( $abs_path ) ) {
				wp_mkdir_p( $abs_path );
			}

			$rel_path = $abs_path . DIRECTORY_SEPARATOR . $path_parts['basename'];
			if ( file_exists( $rel_path ) ) {
				return true;
			} elseif ( ! empty( $model->geodb_path ) && file_exists( $model->geodb_path ) ) {
				// The case if ABSPATH was changed e.g. in wp-config.php.
				return true;
			}

			if ( move_uploaded_file( $model->geodb_path, $rel_path ) ) {
				$model->geodb_path = $rel_path;
				$model->save();
			} else {
				return false;
			}
		}

		return true;
	}
}
