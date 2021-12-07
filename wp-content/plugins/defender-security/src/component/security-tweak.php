<?php

namespace WP_Defender\Component;

use Calotes\Base\Component;
use Calotes\Helper\Array_Cache;
use SplFileObject;
use WP_Defender\Model\Setting\Security_Tweaks;

class Security_Tweak extends Component {

	/**
	 * Use for cache.
	 *
	 * @var Security_Tweaks
	 */
	public $model;

	/**
	 * Safe way to get cached model.
	 *
	 * @return Security_Tweaks
	 */
	protected function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}
		$this->model = new Security_Tweaks();

		return $this->model;
	}

	public function get_issues() {
		$issues       = array();
		$tweaks       = Array_Cache::get( 'tweaks', 'tweaks' );
		$issue_tweaks = $this->get_model()->issues;
		foreach ( $issue_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$tweak_arr = $tweaks[ $slug ]->to_array();
				$issues[]  = array(
					'label' => $tweak_arr['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener' ) . '#' . $slug,
				);
			}
		}

		return $issues;
	}

	public function get_ignored() {
		$ignored        = array();
		$tweaks         = Array_Cache::get( 'tweaks', 'tweaks' );
		$ignored_tweaks = $this->get_model()->ignore;
		foreach ( $ignored_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$tweak_arr = $tweaks[ $slug ]->to_array();
				$ignored[] = array(
					'label' => $tweak_arr['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener&view=ignored' ) . '#' . $slug,
				);
			}
		}

		return $ignored;
	}

	public function get_fixed() {
		$fixed        = array();
		$tweaks       = Array_Cache::get( 'tweaks', 'tweaks' );
		$fixed_tweaks = $this->get_model()->fixed;
		foreach ( $fixed_tweaks as $slug ) {
			if ( isset( $tweaks[ $slug ] ) ) {
				$tweak_arr = $tweaks[ $slug ]->to_array();
				$fixed[]   = array(
					'label' => $tweak_arr['title'],
					'url'   => network_admin_url( 'admin.php?page=wdf-hardener&view=resolved' ) . '#' . $slug,
				);
			}
		}

		return $fixed;
	}

	/**
	 * Get hook line pattern.
	 *
	 * @return string
	 */
	public function get_hook_line_pattern() {
		global $wpdb;

		return '/^\$table_prefix\s*=\s*[\'|\"]' . $wpdb->prefix . '[\'|\"]/';
	}

	/**
	 * @return false
	 */
	public function advanced_check_file() {
		$path_to_wp_config = defender_wp_config_path();

		return file_exists( $path_to_wp_config ) && is_writable( $path_to_wp_config );
	}

	/**
	 * Get file instance.
	 *
	 * @return false|SplFileObject
	 */
	public function file() {
		static $file = false;

		if ( ! $file ) {
			try {
				$file = new SplFileObject( defender_wp_config_path(), 'r+' );
			} catch ( Exception $e ) {
				return false;
			}
		}

		return $file;
	}

	/**
	 * Write to the file.
	 *
	 * @param array $lines
	 *
	 * @return bool
	 */
	public function write( $lines ) {
		$file = $this->file();
		$file->flock( LOCK_EX );
		$file->fseek( 0 );

		$bytes = $file->fwrite( implode( "\n", $lines ) );

		if ( $bytes ) {
			$file->ftruncate( $file->ftell() );
		}

		$file->flock( LOCK_UN );

		return (bool) $bytes;
	}

	/**
	 * Show hosting notice.
	 *
	 * @param string $option
	 *
	 * @return string
	 */
	public function show_hosting_notice( $option ) {

		return sprintf(
		/* translators: %s: Option name. */
			__( 'Some hostings do not allow you to make changes to the wp-config.php file. Please contact your hosting support team to switch %s ON or OFF on your site.', 'wpdef' ),
			$option
		);
	}

	/**
	 * Show hosting notice.
	 *
	 * @param string $option
	 * @param string $code
	 *
	 * @return string
	 */
	public function show_hosting_notice_with_code( $option, $code ) {

		return sprintf(
		/* translators: %s - option */
			__( "Couldn't change the %s in your wp-config.php file. Please change it manually:", 'wpdef' ) . '<p><b>' . $code . '</b></p>',
			'<b>' . $option . '</b>'
		);
	}
}
