<?php
/**
 * Helper functions for IP related tasks.
 *
 * @package WP_Defender\Traits
 */

namespace WP_Defender\Traits;

use WP_Defender\Component\Http\Remote_Address;
use WP_Defender\Component\Smart_Ip_Detection;
use WP_Defender\Model\Setting\Firewall;

trait IP {

	/**
	 * Check if the IP is IPv4 address.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return bool Returns true if the IP address is an IPv4 address, false otherwise.
	 */
	private function is_v4( $ip ) {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 );
	}

	/**
	 * Check if the given IP address is an IPv6 address.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return bool Returns true if the IP address is an IPv6 address, false otherwise.
	 */
	private function is_v6( $ip ) {
		return false !== filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 );
	}

	/**
	 * Check if IPv6 is supported.
	 *
	 * @return bool
	 */
	private function is_v6_support(): bool {
		return defined( 'AF_INET6' );
	}

	/**
	 * Convert IPv4 address to IPv6 address.
	 *
	 * @param  string $ip  IPv4 address.
	 *
	 * @return bool|string
	 */
	private function expand_ip_v6( $ip ) {
		$hex = unpack( 'H*hex', inet_pton( $ip ) );

		return substr( preg_replace( '/([A-f0-9]{4})/', '$1:', $hex['hex'] ), 0, - 1 );
	}

	/**
	 * Convert IPv6 address to binary.
	 *
	 * @param  string $inet  The packed data.
	 * @src https://stackoverflow.com/a/7951507
	 *
	 * @return string
	 */
	private function ine_to_bits( $inet ): string {
		$unpacked = unpack( 'a16', $inet );
		$unpacked = str_split( $unpacked[1] );
		$binaryip = '';
		foreach ( $unpacked as $char ) {
			$binaryip .= str_pad( decbin( ord( $char ) ), 8, '0', STR_PAD_LEFT );
		}

		return $binaryip;
	}

	/**
	 * Compare if an IPv4 address is within a specified range.
	 *
	 * @param  string $ip  The IPv4 address to compare.
	 * @param  string $first_in_range  The lower bound of the range.
	 * @param  string $last_in_range  The upper bound of the range.
	 *
	 * @return bool Returns true if the IP address is within the range, false otherwise.
	 */
	private function compare_v4_in_range( $ip, $first_in_range, $last_in_range ): bool {
		$low  = sprintf( '%u', ip2long( $first_in_range ) );
		$high = sprintf( '%u', ip2long( $last_in_range ) );

		$cip = sprintf( '%u', ip2long( $ip ) );
		if ( $high >= $cip && $cip >= $low ) {
			return true;
		}

		return false;
	}

	/**
	 * Compare if an IPv6 address is within a specified range.
	 *
	 * @param  string $ip  The IPv6 address to compare.
	 * @param  string $first_in_range  The lower bound of the range.
	 * @param  string $last_in_range  The upper bound of the range.
	 *
	 * @return bool Returns true if the IP address is within the range, false otherwise.
	 */
	private function compare_v6_in_range( $ip, $first_in_range, $last_in_range ): bool {
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
	 * Compares an IPv4 address with a CIDR block.
	 *
	 * @param  string $ip  The IPv4 address to compare.
	 * @param  string $block  The CIDR block to compare against.
	 *
	 * @return bool Returns true if the IP address is within the CIDR block, false otherwise.
	 */
	private function compare_cidrv4( $ip, $block ): bool {
		[ $subnet, $bits ] = explode( '/', $block );
		if ( is_null( $bits ) ) {
			$bits = 32;
		}
		$ip      = ip2long( $ip );
		$subnet  = ip2long( $subnet );
		$mask    = - 1 << ( 32 - (int) $bits );
		$subnet &= $mask;// nb: in case the supplied subnet wasn't correctly aligned.

		return ( $ip & $mask ) === $subnet;
	}

	/**
	 * Compares an IPv6 address with a CIDR block.
	 *
	 * @param  string $ip  The IPv6 address to compare.
	 * @param  string $block  The CIDR block to compare against.
	 *
	 * @return bool Returns true if the IP address is within the CIDR block, false otherwise.
	 */
	private function compare_cidrv6( $ip, $block ): bool {
		$ip                = $this->expand_ip_v6( $ip );
		$ip                = inet_pton( $ip );
		$b_ip              = $this->ine_to_bits( $ip );
		[ $subnet, $bits ] = explode( '/', $block );
		$subnet            = $this->expand_ip_v6( $subnet );
		$subnet            = inet_pton( $subnet );
		$b_subnet          = $this->ine_to_bits( $subnet );

		$ip_net_bits = substr( $b_ip, 0, (int) $bits );
		$subnet_bits = substr( $b_subnet, 0, (int) $bits );

		return $ip_net_bits === $subnet_bits;
	}

	/**
	 * Compare ip2 to ip1, true if ip2>ip1, false if not.
	 *
	 * @param  string $ip1  The first IP address to compare.
	 * @param  string $ip2  The second IP address to compare.
	 *
	 * @return bool Returns true if ip2 is greater than ip1, false otherwise.
	 */
	public function compare_ip( $ip1, $ip2 ): bool {
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
	 * Compare if an IP address is within a specified range.
	 *
	 * @param  string $ip  The IP address to compare.
	 * @param  string $first_in_range  The lower bound of the range.
	 * @param  string $last_in_range  The upper bound of the range.
	 *
	 * @return bool Returns true if the IP address is within the range, false otherwise.
	 */
	public function compare_in_range( $ip, $first_in_range, $last_in_range ): bool {
		if ( $this->is_v4( $first_in_range ) && $this->is_v4( $last_in_range ) ) {
			return $this->compare_v4_in_range( $ip, $first_in_range, $last_in_range );
		} elseif ( $this->is_v6( $first_in_range ) && $this->is_v6( $last_in_range ) && $this->is_v6_support() ) {
			return $this->compare_v6_in_range( $ip, $first_in_range, $last_in_range );
		}

		return false;
	}

	/**
	 * Compares an IP address with a CIDR block.
	 *
	 * @param  string $ip  The IP address to compare.
	 * @param  string $block  The CIDR block to compare against.
	 *
	 * @return bool Returns true if the IP address is within the CIDR block, false otherwise.
	 */
	public function compare_cidr( $ip, $block ): bool {
		[ $subnet, $bits ] = explode( '/', $block );
		if ( $this->is_v4( $ip ) && $this->is_v4( $subnet ) ) {
			return $this->compare_cidrv4( $ip, $block );
		} elseif ( $this->is_v6( $ip ) && $this->is_v6( $subnet ) && $this->is_v6_support() ) {
			return $this->compare_cidrv6( $ip, $block );
		}

		return false;
	}

	/**
	 * $ip an be single ip, or a range like xxx.xxx.xxx.xxx - xxx.xxx.xxx.xxx or CIDR.
	 *
	 * @param  string $ip  The IP address to validate.
	 *
	 * @return bool
	 */
	public function validate_ip( $ip ): bool {
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
			[ $ip, $bits ] = explode( '/', $ip );
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
	 *
	 * @src https://en.wikipedia.org/wiki/IPv4#Special-use_addresses
	 * @src https://en.wikipedia.org/wiki/IPv6#Special-use_addresses
	 *
	 * @param  mixed $ip  IP address.
	 *
	 * @return array
	 */
	public function display_validation_message( $ip ): array {
		$errors = array();
		// Case 1: single IP.
		if (
			! stristr( $ip, '-' )
			&& ! stristr( $ip, '/' )
			&& ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$errors[] = sprintf(
			/* translators: %s: IP value. */
				esc_html__( '%s – invalid format', 'wpdef' ),
				'<b>' . $ip . '</b>'
			);
			// Case 2: IP range.
		} elseif ( stristr( $ip, '-' ) ) {
			$ips = explode( '-', $ip );
			foreach ( $ips as $ip_key ) {
				if ( ! filter_var( $ip_key, FILTER_VALIDATE_IP ) ) {
					$errors[] = sprintf(
					/* translators: %s: IP value. */
						esc_html__( '%s – invalid format', 'wpdef' ),
						'<b>' . $ip_key . '</b>'
					);
				}
			}
			if ( ! $this->compare_ip( $ips[0], $ips[1] ) ) {
				$errors[] = sprintf(
				/* translators: 1. IP value. 2. IP value. */
					esc_html__( 'Can\'t compare %1$s with %2$s.', 'wpdef' ),
					'<b>' . $ips[1] . '</b>',
					'<b>' . $ips[0] . '</b>'
				);
			}
			// Case 3: CIDR.
		} elseif ( stristr( $ip, '/' ) ) {
			[ $ip, $bits ] = explode( '/', $ip );
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) && filter_var( $bits, FILTER_VALIDATE_INT ) ) {
				if ( ! $this->is_v4( $ip ) || 0 > $bits || $bits > 32 ) {
					if ( ! $this->is_v6( $ip ) || 0 > $bits || $bits > 128 || ! $this->is_v6_support() ) {
						$errors[] = sprintf(
						/* translators: %s: IP value. */
							esc_html__( '%s – address out of range', 'wpdef' ),
							'<b>' . $ip . '</b>'
						);
					}
				}
			} else {
				$errors[] = sprintf(
				/* translators: %s: IP value. */
					esc_html__( '%s – invalid format', 'wpdef' ),
					'<b>' . $ip . '</b>'
				);
			}
		}

		// @since 2.6.3
		return (array) apply_filters( 'wd_display_ip_validations', $errors );
	}

	/**
	 * Validate IP.
	 *
	 * @param  mixed $ip  IP address.
	 *
	 * @return bool
	 */
	public function check_validate_ip( $ip ): bool {
		// Validate the localhost IP address.
		if ( in_array( $ip, $this->get_localhost_ips(), true ) ) {
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
	 * @return array
	 */
	public function get_user_ip(): array {
		$service = wd_di()->get( Smart_Ip_Detection::class );
		if ( $service->is_smart_ip_detection_enabled() ) {
			$ip_detail = $service->get_smart_ip_detection_details();
			$ips       = isset( $ip_detail[0] ) ? array( $ip_detail[0] ) : array();
		} else {
			$remote_addr = wd_di()->get( Remote_Address::class );
			$ips         = (array) $remote_addr->get_ip_address();
		}

		return $this->filter_user_ips( $ips );
	}

	/**
	 * Get user IP header.
	 *
	 * @return string
	 */
	public function get_user_ip_header(): string {
		$ip_header = '';

		$service = wd_di()->get( Smart_Ip_Detection::class );
		if ( $service->is_smart_ip_detection_enabled() ) {
			$ip_detail = $service->get_smart_ip_detection_details();
			$ip_header = isset( $ip_detail[1] ) ? $ip_detail[1] : '';
		} else {
			$model       = wd_di()->get( Firewall::class );
			$remote_addr = wd_di()->get( Remote_Address::class );
			$ip_header   = $remote_addr->get_http_ip_header_value( esc_html( $model->http_ip_header ) );
		}

		return $ip_header;
	}

	/**
	 * Checks if an IP address is in the correct format within a given array of IP addresses.
	 *
	 * @param  string $ip  The IP address to check.
	 * @param  array  $arr_ips  An array of IP addresses to check against.
	 *
	 * @return bool Returns true if the IP address is in the correct format within the array, false otherwise.
	 */
	public function is_ip_in_format( $ip, $arr_ips ): bool {
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
	 * Filter user IPs.
	 * This function takes an array of user IPs, applies the 'defender_user_ip'
	 * filter to each IP, and returns a unique array of filtered values.
	 *
	 * @param  array $ips  An array of user IPs.
	 *
	 * @return array An array of unique, filtered user IPs.
	 * @since 4.4.2
	 */
	public function filter_user_ips( array $ips ): array {
		$ips_filtered = array();
		foreach ( $ips as $ip ) {
			/**
			 * Filters the user IP.
			 *
			 * @param  string  $ip  The user IP.
			 */
			$ips_filtered[] = apply_filters( 'defender_user_ip', $ip );
		}

		return array_unique( $ips_filtered );
	}

	/**
	 * Use $_SERVER['REMOTE_ADDR'] as the first protection layer to avoid spoofed headers.
	 *
	 * @param  string $blocked_ip  The IP address to check.
	 *
	 * @return string
	 */
	public function check_ip_by_remote_addr( $blocked_ip ): string {
		$ip_addr = defender_get_data_from_request( 'REMOTE_ADDR', 's' );

		return '' !== $ip_addr ? $ip_addr : $blocked_ip;
	}

	/**
	 * Check if the given IP is a private IP.
	 *
	 * @param  string $ip  The IP address to check.
	 *
	 * @return bool True if the IP is a private IP, false otherwise.
	 */
	public function is_private_ip( string $ip ): bool {
		return filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_RES_RANGE | FILTER_FLAG_NO_PRIV_RANGE ) === false;
	}

	/**
	 * Maybe Loopback?
	 * Don't check '127.0.0.0/8' and '::1/128'.
	 *
	 * @return array
	 */
	public function get_localhost_ips(): array {
		return array( '127.0.0.1', '::1' );
	}

	/**
	 * Determines if the current request originates from this server.
	 *
	 * Validates that the request is a loopback from the server by:
	 * 1. Matching the IP against localhost, hostname IP, or a whitelisted server public IP.
	 * 2. Matching the User-Agent against the WordPress core loopback format.
	 *
	 * @return bool True if it's a valid server-originated loopback request; false otherwise.
	 */
	public function request_is_from_server(): bool {
		$user_ips = $this->get_user_ip();
		if ( array() === $user_ips ) {
			return false;
		}

		// Start building list of trusted server IPs.
		$trusted_ips = $this->get_localhost_ips();
		$server_ip   = defender_get_data_from_request( 'SERVER_ADDR', 's' );
		if ( $this->check_validate_ip( $server_ip ) ) {
			$trusted_ips[] = $server_ip;
		}

		$stored_ip = get_site_option( \WP_Defender\Component\Firewall::WHITELIST_SERVER_PUBLIC_IP_OPTION, '' );
		if ( $this->check_validate_ip( $stored_ip ) ) {
			$trusted_ips[] = $stored_ip;
		}

		/**
		 * Filters the list of trusted server IP addresses used to validate server-originated loopback requests.
		 *
		 * This filter allows developers to customize the list of IP addresses considered as trusted sources
		 * for internal server requests. These IPs are checked against the request's origin IP to determine
		 * whether it's a valid server-initiated loopback (e.g., WordPress cron or REST loopback check).
		 *
		 * Common use cases:
		 * - Add public-facing server IPs behind proxies/load balancers.
		 * - Adjust loopback behavior in containerized or cloud environments.
		 * - Remove/override default server hostname resolution or localhost IPs.
		 *
		 * @since 5.3.0
		 *
		 * @param array $trusted_ips An array of trusted server IP addresses.
		 */
		$trusted_ips = (array) apply_filters( 'wp_defender_server_ips', array_unique( $trusted_ips ) );
		// Only return true if the request IP is in the trusted list.
		if ( array() !== array_intersect( $user_ips, $trusted_ips ) ) {
			return true;
		}

		// User-Agent missing? Not a valid loopback.
		$server_agent = defender_get_data_from_request( 'HTTP_USER_AGENT', 's' );
		if ( '' === $server_agent ) {
			return false;
		}

		// User-Agent must match WordPress loopback format.
		$expected_ua = 'WordPress/' . wp_get_wp_version() . '; ' . home_url( '/' );

		return $server_agent === $expected_ua;
	}
}