<?php
/**
 * Handles the implementation of the strict-transport-security header for security purposes.
 *
 * @package WP_Defender\Component\Security_Headers
 */

namespace WP_Defender\Component\Security_Headers;

use WP_Defender\Component\Security_Header;

/**
 * Manages the strict-transport-security header which controls browser features.
 */
class Sh_Strict_Transport extends Security_Header {

	/**
	 * Unique identifier for this security header rule.
	 *
	 * @var string
	 */
	public static $rule_slug = 'sh_strict_transport';

	/**
	 * Get time in seconds
	 *
	 * @return array
	 */
	private function time_in_seconds() {
		return array(
			'1 hour'   => 1 * 3600,
			'24 hours' => 86400,
			'7 days'   => 7 * 86400,
			'30 days'  => 30 * 86400,
			'3 months' => ( 3 * 30 + 1 ) * 86400,
			'6 months' => ( 6 * 30 + 3 ) * 86400,
			'1 year'   => 365 * 86400,
			'2 years'  => 365 * 2 * 86400,
		);
	}

	/**
	 * Check HTTPS
	 *
	 * @return bool
	 */
	private function is_https() {
		return isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && 'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'];
	}

	/**
	 * Checks if the Policy should be applied based on the current settings and site configuration.
	 *
	 * @return bool True if the header should be applied, false otherwise.
	 */
	public function check() {
		$model = $this->get_model();

		if ( ! $model->sh_strict_transport ) {
			return false;
		}
		// 'max-age' directive is required
		if ( ! empty( $model->hsts_cache_duration ) ) {
			return true;
		}
		$headers = $this->head_request( network_site_url(), self::$rule_slug );
		if ( is_wp_error( $headers ) ) {
			$this->log( sprintf( 'Self ping error: %s', $headers->get_error_message() ), wd_internal_log() );

			return false;
		}

		if ( isset( $headers['strict-transport-security'] ) ) {
			$hsts_cache_duration = '';
			$hsts_preload        = 0;
			$include_subdomain   = 0;
			$header_sts          = is_array( $headers['strict-transport-security'] )
				? $headers['strict-transport-security'][0]
				: $headers['strict-transport-security'];

			$content = explode( ';', $header_sts );
			foreach ( $content as $line ) {
				if ( stristr( $line, 'max-age' ) ) {
					$value   = explode( '=', $line );
					$arr     = $this->time_in_seconds();
					$seconds = isset( $value[1] ) ? (int) $value[1] : 0;
					$closest = null;
					$key     = null;
					foreach ( $arr as $k => $item ) {
						if ( is_null( $closest ) || ( ! is_null( $closest ) && ( abs( $seconds - $closest ) > abs( $item - $seconds ) ) ) ) {
							$closest = $item;
							$key     = $k;
						}
					}
					$hsts_cache_duration = $key;
				} elseif ( stristr( $line, 'preload' ) ) {
					$hsts_preload = 1;
				} elseif ( stristr( $line, 'includeSubDomains' ) ) {
					$include_subdomain = 1;
				}
			}

			if ( ( '' !== $hsts_cache_duration )
				|| ( 0 !== $hsts_preload )
				|| ( 0 !== $include_subdomain )
			) {
				if ( is_null( $model->hsts_preload ) && $hsts_preload ) {
					$model->hsts_preload = $hsts_preload;
				}
				if ( is_null( $model->include_subdomain ) && $include_subdomain ) {
					$model->include_subdomain = $include_subdomain;
				}
				if ( is_null( $model->hsts_cache_duration ) && $hsts_cache_duration ) {
					$model->hsts_cache_duration = $hsts_cache_duration;
				}
				$model->save();
			}

			return true;
		}

		return false;
	}

	/**
	 * Extracts the domain suffix from a given domain using a list of public suffixes.
	 *
	 * @param  string $domain  The domain from which to extract the suffix.
	 *
	 * @return mixed Returns the most appropriate domain suffix if found, otherwise false.
	 */
	private function get_domain_suffix( $domain ) {
		require_once dirname( __DIR__ ) . '/public-suffix.php';
		$tlds = get_public_suffix();
		// whitelist development.
		$tlds['localhost'] = 1;
		$parts             = explode( '.', $domain );
		$parts             = array_reverse( $parts );
		$suffix            = '';
		$collection        = array();
		$length            = 0;
		foreach ( $parts as $part ) {
			$suffix    = rtrim( $part . '.' . $suffix, '.' );
			$not_allow = '!' . $suffix;
			if ( isset( $tlds[ $not_allow ] ) ) {
				// this wont be here.
				continue;
			}
			if ( isset( $tlds[ $suffix ] ) ) {
				if ( $length > strlen( $suffix ) ) {
					// put at last.
					$collection[] = $suffix;
				} else {
					array_unshift( $collection, $suffix );
				}
			}
		}
		if ( empty( $collection ) ) {
			return false;
		}

		return $collection[0];
	}

	/**
	 * Parses the domain to extract structural components such as the host, top-level domain (TLD), and subdomain.
	 *
	 * @param  string $domain  The domain URL to parse.
	 *
	 * @return array|false Returns an array with keys 'host', 'tld', and 'subdomain' if parsing is successful,
	 *     otherwise false.
	 */
	public function parse_domain( $domain ) {
		$filter_domain = version_compare( PHP_VERSION, '7.0', '>=' ) ? FILTER_VALIDATE_DOMAIN : FILTER_VALIDATE_URL;
		if ( ! filter_var( $domain, $filter_domain ) ) {
			return false;
		}
		$suffix = $this->get_domain_suffix( $domain );
		if ( ! $suffix ) {
			return false;
		}
		// exclude 'www.'.
		$domain           = str_replace( 'www.', '', $domain );
		$host             = wp_parse_url( $domain, PHP_URL_HOST );
		$host_without_tld = str_replace( $suffix, '', $host );

		$host_without_tld = rtrim( $host_without_tld, '.' );
		$parts            = explode( '.', $host_without_tld );
		if ( 1 === count( $parts ) ) {
			return array(
				'host' => $host,
				'tld'  => $suffix,
			);
		}
		// parse to get the root & subdomain.
		$domain = array_pop( $parts );

		return array(
			'host'      => $host,
			'tld'       => $suffix,
			'subdomain' => str_replace( $domain, '', $host_without_tld ),
		);
	}

	/**
	 * Retrieves miscellaneous data related to the Policy.
	 *
	 * @return array Contains introductory text, mode, and values for the Policy.
	 */
	public function get_misc_data() {
		$model           = $this->get_model();
		$site_url        = network_site_url();
		$domain_data     = $this->parse_domain( $site_url );
		$allow_subdomain = false;

		if ( is_array( $domain_data ) && ! isset( $domain_data['subdomain'] ) ) {
			$allow_subdomain = true;
		} elseif ( ! $domain_data && ! is_multisite() ) {
			// case if a single site installs in a folder, e.g. http://example.com/something/folder/.
			$allow_subdomain = true;
		} elseif ( ! $domain_data && is_multisite() && is_subdomain_install() && is_main_site() ) {
			// case if main site on MU with subdomain install.
			$allow_subdomain = true;
		}

		return array(
			'intro_text'          => esc_html__(
				'The HTTP Strict-Transport-Security response header (HSTS) lets a web site tell browsers that it should only be accessed using HTTPS, instead of using HTTP. This is extremely important for websites that store and process sensitive information like ECommerce stores and helps prevent Protocol Downgrade and Clickjacking attacks.',
				'wpdef'
			),
			'hsts_preload'        => $model->hsts_preload ?? 0,
			'include_subdomain'   => $model->include_subdomain ?? 0,
			'hsts_cache_duration' => $model->hsts_cache_duration ?? '30 days',
			'allow_subdomain'     => $allow_subdomain,
		);
	}

	/**
	 * Registers hooks related to sending headers.
	 */
	public function add_hooks() {
		add_action( 'send_headers', array( $this, 'append_header' ) );
	}

	/**
	 * Appends the header to the response.
	 */
	public function append_header() {
		if ( headers_sent() ) {
			return;
		}

		if ( ! $this->maybe_submit_header( 'Strict-Transport-Security', false ) ) {
			return;
		}
		$model = $this->get_model();
		// header is ignored by the browser when your site is accessed using HTTP.
		if ( true === $model->sh_strict_transport ) {
			$headers         = 'Strict-Transport-Security:';
			$default_max_age = 604800;
			if ( isset( $model->hsts_cache_duration ) && ! empty( $model->hsts_cache_duration ) ) {
				$arr = $this->time_in_seconds();
				// set default for a week, so RIPs wont waring weak header.
				$seconds = $arr[ $model->hsts_cache_duration ] ?? $default_max_age;
				if ( ! is_null( $seconds ) ) {
					$headers .= ' max-age=' . $seconds;
				}
			} else {
				// set default for a week.
				$headers .= ' max-age=' . $default_max_age;
			}

			if ( '1' === (string) $model->include_subdomain ) {
				$headers .= ' ; includeSubDomains';
			}
			if ( '1' === (string) $model->hsts_preload ) {
				$headers .= ' ; preload';
			}

			header( $headers );
		}
	}

	/**
	 * Retrieves the title of the Policy.
	 *
	 * @return string The title of the Policy.
	 */
	public function get_title() {
		return esc_html__( 'Strict Transport', 'wpdef' );
	}
}