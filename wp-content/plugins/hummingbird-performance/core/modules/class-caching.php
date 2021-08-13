<?php
/**
 * Caching module.
 *
 * @package Hummingbird
 */

namespace Hummingbird\Core\Modules;

use Hummingbird\Core\Module_Server;
use Hummingbird\Core\Utils;
use WP_Http_Cookie;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Caching
 */
class Caching extends Module_Server {

	/**
	 * Module slug.
	 *
	 * @var string
	 */
	protected $transient_slug = 'caching';

	/**
	 * Module status.
	 *
	 * @var array $status
	 */
	public $status;

	/**
	 * Activate module.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function enable() {
		// Enable caching in .htaccess (only for apache servers).
		$result = Module_Server::save_htaccess( 'caching' );
		if ( $result ) {
			// Clear saved status.
			Utils::get_module( 'caching' )->clear_cache();
			return true;
		}

		return false;
	}

	/**
	 * Deactivate module.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function disable() {
		// Disable caching in htaccess (only for apache servers).
		$result = Module_Server::unsave_htaccess( 'caching' );
		if ( $result ) {
			// Clear saved status.
			Utils::get_module( 'caching' )->clear_cache();
			return true;
		}

		return false;
	}

	/**
	 * Analyze data. Overwrites parent method.
	 *
	 * @param bool $check_api If set to true, the api can be checked.
	 *
	 * @return array
	 */
	public function analyze_data( $check_api = false ) {
		$files = array(
			'JavaScript' => WPHB_DIR_URL . 'core/modules/dummy/dummy-js.js',
			'CSS'        => WPHB_DIR_URL . 'core/modules/dummy/dummy-style.css',
			'Media'      => WPHB_DIR_URL . 'core/modules/dummy/dummy-media.pdf',
			'Images'     => WPHB_DIR_URL . 'core/modules/dummy/dummy-image.png',
		);

		$results = array();
		$try_api = false;
		foreach ( $files as $type  => $file ) {

			$cookies = array();
			foreach ( $_COOKIE as $name => $value ) {
				if ( strpos( $name, 'wordpress_' ) > -1 ) {
					$cookies[] = new WP_Http_Cookie(
						array(
							'name'  => $name,
							'value' => $value,
						)
					);
				}
			}

			$args = array(
				'cookies'   => $cookies,
				'sslverify' => false,
			);

			$result = wp_remote_head( $file, $args );

			$this->log( '----- analyzing headers for ' . $file );
			$this->log( 'args: ' );
			if ( isset( $args['cookies'] ) ) {
				unset( $args['cookies'] );
			}
			$this->log( $args );
			$this->log( 'result: ' );
			$this->log( $result );

			$cache_control    = wp_remote_retrieve_header( $result, 'cache-control' );
			$results[ $type ] = false;
			if ( $cache_control ) {
				if ( is_array( $cache_control ) ) {
					// Join the cache control header into a single string.
					$cache_control = join( ' ', $cache_control );
				}
				if ( preg_match( '/max\-age=([0-9]*)/', $cache_control, $matches ) ) {
					if ( isset( $matches[1] ) ) {
						$seconds          = absint( $matches[1] );
						$results[ $type ] = $seconds;
					}
				}
			} else {
				$try_api = true;
			}
		}

		// Will only trigger on 're-check status' button click and there are some false values.
		if ( $try_api && $check_api ) {
			// Get the API results.
			$api         = Utils::get_api();
			$api_results = $api->performance->check_cache();

			// This will prevent errors on local hosts and when API is not reachable.
			if ( ! is_wp_error( $api_results ) ) {
				$api_results = get_object_vars( $api_results );

				foreach ( $files as $type => $file ) {
					$ltype = strtolower( $type );
					if ( ! isset( $api_results[ $ltype ]->response_error ) && ! isset( $api_results[ $ltype ]->http_request_failed ) && absint( $api_results[ $ltype ] ) > 0 ) {
						$results[ $type ] = absint( $api_results[ $ltype ] );
					}
				}
			}
		}

		do_action( 'wphb_caching_analize_data', $results );

		return $results;
	}

	/**
	 * Get code for Nginx
	 *
	 * @param array $expiry_times Type expiry times (javascript, css...). Used with AJAX call caching_reload_snippet.
	 *
	 * @return string
	 */
	public function get_nginx_code( $expiry_times = array() ) {
		if ( empty( $expiry_times ) ) {
			$options = $this->get_options();
		} else {
			$options = $expiry_times;
		}

		$assets_expiration = explode( '/', $options['expiry_javascript'] );
		$assets_expiration = $assets_expiration[0];
		$css_expiration    = explode( '/', $options['expiry_css'] );
		$css_expiration    = $css_expiration[0];
		$media_expiration  = explode( '/', $options['expiry_media'] );
		$media_expiration  = $media_expiration [0];
		$images_expiration = explode( '/', $options['expiry_images'] );
		$images_expiration = $images_expiration[0];

		$code = 'location ~* \.(txt|xml|js)$ {
    expires %%ASSETS%%;
}

location ~* \.(css)$ {
    expires %%CSS%%;
}

location ~* \.(flv|ico|pdf|avi|mov|ppt|doc|mp3|wmv|wav|mp4|m4v|ogg|webm|aac|eot|ttf|otf|woff|woff2|svg)$ {
    expires %%MEDIA%%;
}

location ~* \.(jpg|jpeg|png|gif|swf|webp)$ {
    expires %%IMAGES%%;
}';

		$code = str_replace( '%%MEDIA%%', $media_expiration, $code );
		$code = str_replace( '%%IMAGES%%', $images_expiration, $code );
		$code = str_replace( '%%ASSETS%%', $assets_expiration, $code );
		$code = str_replace( '%%CSS%%', $css_expiration, $code );

		return $code;
	}

	/**
	 * Get code for Apache
	 *
	 * @param array $expiry_times Type expiry times (javascript, css...). Used with AJAX call caching_reload_snippet.
	 *
	 * @return string
	 */
	public function get_apache_code( $expiry_times = array() ) {
		if ( empty( $expiry_times ) ) {
			$options = $this->get_options();
		} else {
			$options = $expiry_times;
		}

		$assets_expiration = explode( '/', $options['expiry_javascript'] );
		$assets_expiration = $assets_expiration[1];
		$css_expiration    = explode( '/', $options['expiry_css'] );
		$css_expiration    = $css_expiration[1];
		$media_expiration  = explode( '/', $options['expiry_media'] );
		$media_expiration  = $media_expiration [1];
		$images_expiration = explode( '/', $options['expiry_images'] );
		$images_expiration = $images_expiration[1];

		$code = '<IfModule mod_expires.c>
ExpiresActive On
ExpiresDefault A0

<FilesMatch "\.(txt|xml|js)$">
ExpiresDefault %%ASSETS%%
</FilesMatch>

<FilesMatch "\.(css)$">
ExpiresDefault %%CSS%%
</FilesMatch>

<FilesMatch "\.(flv|ico|pdf|avi|mov|ppt|doc|mp3|wmv|wav|mp4|m4v|ogg|webm|aac|eot|ttf|otf|woff|woff2|svg)$">
ExpiresDefault %%MEDIA%%
</FilesMatch>

<FilesMatch "\.(jpg|jpeg|png|gif|swf|webp)$">
ExpiresDefault %%IMAGES%%
</FilesMatch>
</IfModule>

<IfModule mod_headers.c>
  <FilesMatch "\.(txt|xml|js)$">
   Header set Cache-Control "max-age=%%ASSETS_HEAD%%"
  </FilesMatch>

  <FilesMatch "\.(css)$">
   Header set Cache-Control "max-age=%%CSS_HEAD%%"
  </FilesMatch>

  <FilesMatch "\.(flv|ico|pdf|avi|mov|ppt|doc|mp3|wmv|wav|mp4|m4v|ogg|webm|aac|eot|ttf|otf|woff|woff2|svg)$">
   Header set Cache-Control "max-age=%%MEDIA_HEAD%%"
  </FilesMatch>

  <FilesMatch "\.(jpg|jpeg|png|gif|swf|webp)$">
   Header set Cache-Control "max-age=%%IMAGES_HEAD%%"
  </FilesMatch>
</IfModule>';

		$code = str_replace( '%%MEDIA%%', $media_expiration, $code );
		$code = str_replace( '%%IMAGES%%', $images_expiration, $code );
		$code = str_replace( '%%ASSETS%%', $assets_expiration, $code );
		$code = str_replace( '%%CSS%%', $css_expiration, $code );

		$code = str_replace( '%%MEDIA_HEAD%%', ltrim( $media_expiration, 'A' ), $code );
		$code = str_replace( '%%IMAGES_HEAD%%', ltrim( $images_expiration, 'A' ), $code );
		$code = str_replace( '%%ASSETS_HEAD%%', ltrim( $assets_expiration, 'A' ), $code );
		$code = str_replace( '%%CSS_HEAD%%', ltrim( $css_expiration, 'A' ), $code );

		return $code;
	}

	/**
	 * Get code for IIS
	 *
	 * @return string
	 */
	public function get_iis_code() {
		return '';
	}

	/**
	 * Get code for IIS 7
	 *
	 * @return string
	 */
	public function get_iis_7_code() {
		return '';
	}

	/**
	 * Get an array of caching frequencies.
	 *
	 * @return array
	 */
	public static function get_frequencies() {
		return array(
			'1h/A3600'     => __( '1 hour', 'wphb' ),
			'3h/A10800'    => __( '3 hours', 'wphb' ),
			'4h/A14400'    => __( '4 hours', 'wphb' ),
			'5h/A18000'    => __( '5 hours', 'wphb' ),
			'6h/A21600'    => __( '6 hours', 'wphb' ),
			'12h/A43200'   => __( '12 hours', 'wphb' ),
			'16h/A57600'   => __( '16 hours', 'wphb' ),
			'20h/A72000'   => __( '20 hours', 'wphb' ),
			'1d/A86400'    => __( '1 day', 'wphb' ),
			'2d/A172800'   => __( '2 days', 'wphb' ),
			'3d/A259200'   => __( '3 days', 'wphb' ),
			'4d/A345600'   => __( '4 days', 'wphb' ),
			'5d/A432000'   => __( '5 days', 'wphb' ),
			'8d/A691200'   => __( '8 days', 'wphb' ),
			'16d/A1382400' => __( '16 days', 'wphb' ),
			'24d/A2073600' => __( '24 days', 'wphb' ),
			'1M/A2592000'  => __( '1 month', 'wphb' ),
			'2M/A5184000'  => __( '2 months', 'wphb' ),
			'3M/A7776000'  => __( '3 months', 'wphb' ),
			'6M/A15552000' => __( '6 months', 'wphb' ),
			'1y/A31536000' => __( '1 year', 'wphb' ),
		);
	}

	/**
	 * Get recommended caching values.
	 *
	 * @return array
	 */
	public function get_recommended_caching_values() {
		return array(
			'css'        => array(
				'label' => __( '1 year', 'wphb' ),
				'value' => YEAR_IN_SECONDS,
			),
			'javascript' => array(
				'label' => __( '1 year', 'wphb' ),
				'value' => YEAR_IN_SECONDS,
			),
			'media'      => array(
				'label' => __( '1 year', 'wphb' ),
				'value' => YEAR_IN_SECONDS,
			),
			'images'     => array(
				'label' => __( '1 year', 'wphb' ),
				'value' => YEAR_IN_SECONDS,
			),
		);
	}

	/**
	 * Get default caching types for HB or Cloudflare.
	 *
	 * @since 1.7.1
	 * @return array
	 */
	public function get_types() {
		$caching_types = array();

		$caching_types['javascript'] = 'txt | xml | js';
		$caching_types['css']        = 'css';
		$caching_types['media']      = 'flv | ico | pdf | avi | mov | ppt | doc | mp3 | wmv | wav | mp4 | m4v | ogg | webm | aac | eot | ttf | otf | woff | woff2 | svg';
		$caching_types['images']     = 'jpg | jpeg | png | gif | swf | webp';

		$cloudflare = Utils::get_module( 'cloudflare' );

		if ( $cloudflare->is_connected() && $cloudflare->is_zone_selected() ) {
			$caching_types['javascript'] = 'txt | xml | js';
			$caching_types['css']        = 'css';
			$caching_types['media']      = 'flv | ico | pdf | avi | mov | ppt | doc | mp3 | wmv | wav | mp4 | m4v | ogg | webm | aac | eot | ttf | otf | woff | woff2 | svg';
			$caching_types['images']     = 'jpg | jpeg | png | gif | swf | webp';
			$caching_types['cloudflare'] = 'bmp | pict | csv | pls | tif | tiff | eps | ejs | midi | mid | woff2 | svgz | docx | xlsx | xls | pptx | ps | class | jar';
		}

		return $caching_types;
	}

}
