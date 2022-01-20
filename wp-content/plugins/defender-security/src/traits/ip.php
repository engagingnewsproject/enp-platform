<?php

namespace WP_Defender\Traits;

use WP_Defender\Extra\IP_Helper;

trait IP {
	/**
	 * @param $ip
	 *
	 * @return mixed
	 */
	private function is_v4( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}

	/**
	 * @param $ip
	 *
	 * @return mixed
	 */
	private function is_v6( $ip ) {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
	}

	/**
	 * @return bool
	 */
	private function is_v6_support() {
		return defined( 'AF_INET6' );
	}

	/**
	 * @param $ip
	 *
	 * @return bool|string
	 */
	private function expand_ip_v6( $ip ) {
		$hex = unpack( 'H*hex', inet_pton( $ip ) );
		$ip  = substr( preg_replace( '/([A-f0-9]{4})/', '$1:', $hex['hex'] ), 0, - 1 );

		return $ip;
	}

	/**
	 * @param $inet
	 *
	 * @src https://stackoverflow.com/a/7951507
	 * @return string
	 */
	private function ine_to_bits( $inet ) {
		$unpacked = unpack( 'a16', $inet );
		$unpacked = str_split( $unpacked[1] );
		$binaryip = '';
		foreach ( $unpacked as $char ) {
			$binaryip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		return $binaryip;
	}

	/**
	 * @param $ip
	 * @param $first_in_range
	 * @param $last_in_range
	 *
	 * @return bool
	 */
	private function compare_v4_in_range( $ip, $first_in_range, $last_in_range ) {
		$low  = sprintf( '%u', ip2long( $first_in_range ) );
		$high = sprintf( '%u', ip2long( $last_in_range ) );

		$cip = sprintf( '%u', ip2long( $ip ) );
		if ( $high >= $cip && $cip >= $low ) {
			return true;
		}

		return false;
	}

	/**
	 * @param $ip
	 * @param $first_in_range
	 * @param $last_in_range
	 *
	 * @return bool
	 */
	private function compare_v6_in_range( $ip, $first_in_range, $last_in_range ) {
		$first_in_range = inet_pton( $this->expand_ip_v6( $first_in_range ) );
		$last_in_range  = inet_pton( $this->expand_ip_v6( $last_in_range ) );
		$ip             = inet_pton( $this->expand_ip_v6( $ip ) );

		if ( ( strlen( $ip ) === strlen( $first_in_range ) )
			&& ( $ip >= $first_in_range && $ip <= $last_in_range ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @param $ip
	 * @param $block
	 *
	 * @src http://stackoverflow.com/a/594134
	 * @return bool
	 */
	private function compare_cidrv4( $ip, $block ) {
		list ( $subnet, $bits ) = explode( '/', $block );
		if ( is_null( $bits ) ) {
			$bits = 32;
		}
		$ip      = ip2long( $ip );
		$subnet  = ip2long( $subnet );
		$mask    = - 1 << ( 32 - $bits );
		$subnet &= $mask;// nb: in case the supplied subnet wasn't correctly aligned

		return ( $ip & $mask ) == $subnet;// phpcs:ignore
	}

	/**
	 * @param $ip
	 * @param $block
	 *
	 * @return bool
	 */
	private function compare_cidrv6( $ip, $block ) {
		$ip                     = $this->expand_ip_v6( $ip );
		$ip                     = inet_pton( $ip );
		$b_ip                   = $this->ine_to_bits( $ip );
		list ( $subnet, $bits ) = explode( '/', $block );
		$subnet                 = $this->expand_ip_v6( $subnet );
		$subnet                 = inet_pton( $subnet );
		$b_subnet               = $this->ine_to_bits( $subnet );

		$ip_net_bits = substr( $b_ip, 0, $bits );
		$subnet_bits = substr( $b_subnet, 0, $bits );

		return $ip_net_bits === $subnet_bits;
	}

	/**
	 * Compare ip2 to ip1, true if ip2>ip1, false if not.
	 *
	 * @param $ip1
	 * @param $ip2
	 *
	 * @return bool
	 */
	public function compare_ip( $ip1, $ip2 ) {
		if ( $this->is_v4( $ip1 ) && $this->is_v4( $ip2 ) ) {
			if ( sprintf( '%u', ip2long( $ip2 ) ) - sprintf( '%u', ip2long( $ip1 ) ) > 0 ) {
				return true;
			}
		} elseif ( $this->is_v6( $ip1 ) && $this->is_v6( $ip2 ) && $this->is_v6_support() ) {
			$ip1 = inet_pton( $this->expand_ip_v6( $ip1 ) );
			$ip2 = inet_pton( $this->expand_ip_v6( $ip2 ) );

			return $ip2 > $ip1;
		}

		return false;
	}

	/**
	 * @param $ip
	 * @param $first_in_range
	 * @param $last_in_range
	 *
	 * @return bool
	 */
	public function compare_in_range( $ip, $first_in_range, $last_in_range ) {
		if ( $this->is_v4( $first_in_range ) && $this->is_v4( $last_in_range ) ) {
			return $this->compare_v4_in_range( $ip, $first_in_range, $last_in_range );
		} elseif ( $this->is_v6( $first_in_range ) && $this->is_v6( $last_in_range ) && $this->is_v6_support() ) {
			$this->compare_v6_in_range( $ip, $first_in_range, $last_in_range );
		}

		return false;
	}

	public function compare_cidr( $ip, $block ) {
		list ( $subnet, $bits ) = explode( '/', $block );
		if ( $this->is_v4( $ip ) && $this->is_v4( $subnet ) ) {
			return $this->compare_cidrv4( $ip, $block );
		} elseif ( $this->is_v6( $ip ) && $this->is_v6( $subnet ) && $this->is_v6_support() ) {
			return $this->compare_cidrv6( $ip, $block );
		}

		return false;
	}

	/**
	 * $ip an be single ip, or a range like xxx.xxx.xxx.xxx - xxx.xxx.xxx.xxx or CIDR.
	 * @param $ip
	 *
	 * @return bool
	 */
	public function validate_ip( $ip ) {
		if (
			! stristr( $ip, '-' )
			&& ! stristr( $ip, '/' )
			&& filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			// Only ip, no '-', '/' symbols.
			return true;
		} elseif ( stristr( $ip, '-' ) ) {
			$ips = explode( '-', $ip );
			foreach ( $ips as $ip_key ) {
				if ( ! filter_var( $ip_key, FILTER_VALIDATE_IP ) ) {
					return false;
				}
			}
			if ( $this->compare_ip( $ips[0], $ips[1] ) ) {
				return true;
			}
		} elseif ( stristr( $ip, '/' ) ) {
			list( $ip, $bits ) = explode( '/', $ip );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) && filter_var( $bits, FILTER_VALIDATE_INT ) ) {
				if ( $this->is_v4( $ip ) && 0 <= $bits && $bits <= 32 ) {
					return true;
				} elseif ( $this->is_v6( $ip ) && 0 <= $bits && $bits <= 128 && $this->is_v6_support() ) {
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Display a message if IP is non-valid. The cases:
	 * 1) single IP (no '-', '/' symbols),
	 * 2) IP range,
	 * 3) CIDR.
	 * Ignore cases with private, reserved ranges.
	 * @src https://en.wikipedia.org/wiki/IPv4#Special-use_addresses
	 * @src https://en.wikipedia.org/wiki/IPv6#Special-use_addresses
	 * @param $ip
	 *
	 * @return array
	*/
	public function display_validation_message( $ip ) {
		$errors = array();
		// Case1: single IP.
		if (
			! stristr( $ip, '-' )
			&& ! stristr( $ip, '/' )
			&& ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$errors[] = sprintf(
			/* translators: ... */
				__( '<b>%s</b> – invalid format', 'wpdef' ),
				$ip
			);
		// Case2: IP range.
		} elseif ( stristr( $ip, '-' ) ) {
			$ips = explode( '-', $ip );
			foreach ( $ips as $ip_key ) {
				if ( ! filter_var( $ip_key, FILTER_VALIDATE_IP ) ) {
					$errors[] = sprintf(
					/* translators: ... */
						__( '<b>%s</b> – invalid format', 'wpdef' ),
						$ip_key
					);
				}
			}
			if ( ! $this->compare_ip( $ips[0], $ips[1] ) ) {
				$errors[] = sprintf(
				/* translators: ... */
					__( "Can't compare <b>%s</b> with <b>%s</b>.", 'wpdef' ),
					$ips[1],
					$ips[0]
				);
			}
		// Case3: CIDR.
		} elseif ( stristr( $ip, '/' ) ) {
			list( $ip, $bits ) = explode( '/', $ip );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) && filter_var( $bits, FILTER_VALIDATE_INT ) ) {
				if ( $this->is_v4( $ip ) && 0 <= $bits && $bits <= 32 ) {
					// IPv4 is correct.
				} elseif ( $this->is_v6( $ip ) && 0 <= $bits && $bits <= 128 && $this->is_v6_support() ) {
					// IPv6 is correct.
				} else {
					$errors[] = sprintf(
					/* translators: ... */
						__( '<b>%s</b> – address out of range', 'wpdef' ),
						$ip
					);
				}
			} else {
				$errors[] = sprintf(
				/* translators: ... */
					__( '<b>%s</b> – invalid format', 'wpdef' ),
					$ip
				);
			}
		}
		// @since 2.6.3
		return apply_filters( 'wd_display_ip_validations', $errors );
	}

	/**
	 * Check if there are any cloudflare headers in the request.
	 *
	 * @return bool
	 */
	private function is_cloudflare_request() {
		if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_CF_IPCOUNTRY'] ) ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_CF_RAY'] ) ) {
			return true;
		}
		if ( isset( $_SERVER['HTTP_CF_VISITOR'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * We fetch the ip range here.
	 * https://www.cloudflare.com/ips/
	 *
	 * @since 2.5.0 Update the ip range.
	 *
	 * @return array
	 */
	private function cloudflare_ip_ranges() {
		return array(
			array(
				'173.245.48.0/20',
				'103.21.244.0/22',
				'103.22.200.0/22',
				'103.31.4.0/22',
				'141.101.64.0/18',
				'108.162.192.0/18',
				'190.93.240.0/20',
				'188.114.96.0/20',
				'197.234.240.0/22',
				'198.41.128.0/17',
				'162.158.0.0/15',
				'172.64.0.0/13',
				'131.0.72.0/22',
				'104.16.0.0/13',
				'104.24.0.0/14',
			),
			array(
				'2400:cb00::/32',
				'2606:4700::/32',
				'2803:f800::/32',
				'2405:b500::/32',
				'2405:8100::/32',
				'2a06:98c0::/29',
				'2c0f:f248::/32',
			),
		);
	}

	/**
	 * Check if the request is from cloudflare. If it is, we get the IP.
	 *
	 * @return mixed
	 */
	private function cloudflare_ip() {
		$ip = null;
		if ( $this->is_cloudflare_request() ) {
			// This looks like it come from cloudflare, so this should contain the actual IP,
			// and REMOTE_ADDR is contained cloudflare IP.
			list( $cloudflare_ipv4_range, $cloudflare_ipv6_range ) = $this->cloudflare_ip_ranges();
			$ip_helper = new IP_Helper();
			if ( $this->is_v4( $ip ) ) {
				foreach ( $cloudflare_ipv4_range as $cf_ip ) {
					if ( $ip_helper->ipv4_in_range( $ip, $cf_ip ) ) {
						$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
						break;
					}
				}
			} elseif ( $this->is_v6( $ip ) ) {
				foreach ( $cloudflare_ipv6_range as $cf_ip ) {
					if ( $ip_helper->ipv6_in_range( $ip, $cf_ip ) ) {
						$ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
						break;
					}
				}
			}
		}

		return $ip;
	}

	/**
	 * Validate IP.
	 * @param $ip
	 *
	 * @return bool
	 */
	public function check_validate_ip( $ip ) {
		// Validate the localhost IP address.
		if ( in_array( $ip, array( '127.0.0.1', '::1' ), true ) ) {
			return true;
		}

		$filter_flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
		// @since 2.4.7
		if ( apply_filters( 'wp_defender_filtered_internal_ip', false ) ) {
			// Todo: improve display of IP log when filtering reserved or private IPv4/IPv6 ranges.
			$filter_flags = $filter_flags | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
		}

		if ( false === filter_var( $ip, FILTER_VALIDATE_IP, $filter_flags ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get user IP.
	 *
	 * @return string
	 */
	public function get_user_ip() {
		// Check if it's any cloudflare IP.
		$cf_ip = $this->cloudflare_ip();
		if ( ! empty( $cf_ip ) && filter_var( $cf_ip, FILTER_VALIDATE_IP ) ) {
			return apply_filters( 'defender_user_ip', $cf_ip );
		}

		$headers = array(
			'HTTP_CLIENT_IP',
			'HTTP_X_REAL_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_X_CLUSTER_CLIENT_IP',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		);
		$ip = '';
		foreach ( $headers as $key ) {
			if ( array_key_exists( $key, $_SERVER ) && ! empty( $_SERVER[ $key ] ) ) {
				$ip_array = explode( ',', $_SERVER[ $key ] );
				$tmp_ip   = array_shift( $ip_array );
				$tmp_ip   = trim( $tmp_ip );
				if ( $this->check_validate_ip( $tmp_ip ) ) {
					$ip = $tmp_ip;
					break;
				}
			}
		}

		return apply_filters( 'defender_user_ip', $ip );
	}
}
