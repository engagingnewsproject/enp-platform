<?php
/**
 * Asset Optimization AJAX actions.
 *
 * @since 2.7.2
 * @package Hummingbird\Admin\Ajax\Caching
 */

namespace Hummingbird\Admin\Ajax;

use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Minify.
 */
class Minify {

	/**
	 * Minify constructor.
	 */
	public function __construct() {
		$endpoints = array(
			'minify_status',
			'minify_clear_cache',
			'minify_recheck_files',
			'minify_reset_settings',
			'minify_save_settings',
		);

		foreach ( $endpoints as $endpoint ) {
			add_action( "wp_ajax_wphb_react_{$endpoint}", array( $this, $endpoint ) );
		}
	}

	/**
	 * Get asset optimization status.
	 *
	 * @since 2.7.2
	 */
	public function minify_status() {
		check_ajax_referer( 'wphb-fetch' );

		$options = Utils::get_module( 'minify' )->get_options();

		if ( 'basic' === $options['type'] ) {
			$excluded_styles  = $options['dont_minify']['styles'];
			$excluded_scripts = $options['dont_minify']['scripts'];
		} else {
			$excluded_styles  = array_unique( array_merge( $options['dont_minify']['styles'], $options['dont_combine']['styles'] ) );
			$excluded_scripts = array_unique( array_merge( $options['dont_minify']['scripts'], $options['dont_combine']['scripts'] ) );
		}

		wp_send_json_success(
			array(
				'assets'     => \Hummingbird\Core\Modules\Minify\Sources_Collector::get_collection(),
				'enabled'    => array(
					'styles'  => $options['do_assets']['styles'],
					'scripts' => $options['do_assets']['scripts'],
				),
				'exclusions' => array(
					'styles'  => $excluded_styles,
					'scripts' => $excluded_scripts,
				),
				'view'       => $options['type'],
			)
		);
	}

	/**
	 * Fetch/refresh asset optimization status.
	 *
	 * @since 2.7.2
	 */
	public function minify_clear_cache() {
		check_ajax_referer( 'wphb-fetch' );

		Utils::get_module( 'minify' )->clear_cache( false );

		wp_send_json_success();
	}

	/**
	 * Re-check files.
	 *
	 * @since 2.7.2
	 */
	public function minify_recheck_files() {
		check_ajax_referer( 'wphb-fetch' );

		Utils::get_module( 'minify' )->clear_cache( false );

		$collector = Utils::get_module( 'minify' )->sources_collector;
		$collector::clear_collection();

		// Activate minification if is not.
		Utils::get_module( 'minify' )->toggle_service( true );
		Utils::get_module( 'minify' )->scanner->init_scan();

		wp_send_json_success();
	}

	/**
	 * Reset asset optimization settings.
	 *
	 * @since 2.7.2
	 */
	public function minify_reset_settings() {
		check_ajax_referer( 'wphb-fetch' );

		$options = Utils::get_module( 'minify' )->get_options();

		$defaults = Settings::get_default_settings();

		$options['do_assets']    = $defaults['minify']['do_assets'];
		$options['dont_minify']  = $defaults['minify']['dont_minify'];
		$options['dont_combine'] = $defaults['minify']['dont_combine'];

		Utils::get_module( 'minify' )->update_options( $options );
		Utils::get_module( 'minify' )->clear_cache( false );

		wp_send_json_success();
	}

	/**
	 * Save asset optimization settings.
	 *
	 * @since 2.7.2
	 */
	public function minify_save_settings() {
		check_ajax_referer( 'wphb-fetch' );

		$settings = filter_input( INPUT_POST, 'data', FILTER_DEFAULT, FILTER_SANITIZE_STRIPPED );
		$settings = json_decode( html_entity_decode( $settings ), true );

		$options = Utils::get_module( 'minify' )->get_options();

		$type_changed = false;
		// Update selected type.
		if ( isset( $settings['type'] ) && in_array( $settings['type'], array( 'speedy', 'basic' ), true ) ) {
			$type_changed    = $options['type'] !== $settings['type'];
			$options['type'] = $settings['type'];
		}

		$types = array( 'scripts', 'styles' );

		$collections = \Hummingbird\Core\Modules\Minify\Sources_Collector::get_collection();

		$assets = json_decode( html_entity_decode( $settings['data'] ), true );

		foreach ( $types as $type ) {
			// Save the type selection.
			// More assets than excluded - enable the option in UI, even if it was manually disabled.
			if ( ! $options['do_assets'][ $type ] && count( $collections[ $type ] ) > count( $assets[ $type ] ) ) {
				$options['do_assets'][ $type ] = true;
			} else {
				$options['do_assets'][ $type ] = isset( $settings[ $type ] ) && false === $settings[ $type ] ? false : true;
			}

			// By default we minify and combine everything.
			$options['dont_minify'][ $type ] = array();
			if ( 'speedy' === $settings['type'] ) {
				$options['dont_combine'][ $type ] = array();
			} else {
				$options['dont_combine'][ $type ] = array_keys( $collections[ $type ] );
			}

			// At this point we have no setting field? Weird, let's skip further processing.
			if ( ! isset( $settings[ $type ] ) ) {
				continue;
			}

			$handles = array();
			if ( false === $options['do_assets'][ $type ] ) {
				// If an option (CSS/JS) is disabled, put all handles in the don't do list.
				$handles = array_keys( $collections[ $type ] );
			} elseif ( count( $assets[ $type ] ) !== count( $collections[ $type ] ) ) {
				// If the exclusion does not have all the assets, exclude the selected ones.
				$handles = $assets[ $type ];
			}

			$options['dont_minify'][ $type ] = $handles;
			// We've already excluded all the handles for basic above.
			if ( 'speedy' === $settings['type'] ) {
				$options['dont_combine'][ $type ] = $handles;
			}
		}

		Utils::get_module( 'minify' )->update_options( $options );
		Utils::get_module( 'minify' )->clear_cache( false );

		if ( $type_changed && 'speedy' === $settings['type'] ) {
			$type_changed = sprintf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html__( 'Speedy optimization is now active. Plugins and theme files are being queued for processing and will gradually be optimized as your visitors request them. For more information on how automatic optimization works, you can check %1$sHow Does It Work%2$s section.', 'wphb' ),
				"<a href='#' id='wphb-basic-hdiw-link' data-modal-open='automatic-ao-hdiw-modal-content'>",
				'</a>'
			);
		}

		if ( $type_changed && 'basic' === $settings['type'] ) {
			$type_changed = sprintf( /* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				esc_html__( 'Basic optimization is now active. Plugins and theme files are now being queued for processing and will gradually be optimized as they are requested by your visitors. For more information on how automatic optimization works, you can check %1$sHow Does It Work%2$s section.', 'wphb' ),
				"<a href='#' id='wphb-basic-hdiw-link' data-modal-open='automatic-ao-hdiw-modal-content'>",
				'</a>'
			);
		}

		if ( 'basic' === $options['type'] ) {
			$excluded_styles  = $options['dont_minify']['styles'];
			$excluded_scripts = $options['dont_minify']['scripts'];
		} else {
			$excluded_styles  = array_unique( array_merge( $options['dont_minify']['styles'], $options['dont_combine']['styles'] ) );
			$excluded_scripts = array_unique( array_merge( $options['dont_minify']['scripts'], $options['dont_combine']['scripts'] ) );
		}

		wp_send_json_success(
			array(
				'assets'      => $collections,
				'enabled'     => array(
					'styles'  => $options['do_assets']['styles'],
					'scripts' => $options['do_assets']['scripts'],
				),
				'exclusions'  => array(
					'styles'  => $excluded_styles,
					'scripts' => $excluded_scripts,
				),
				'typeChanged' => $type_changed,
			)
		);
	}

}
