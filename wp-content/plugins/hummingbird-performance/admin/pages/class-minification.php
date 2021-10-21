<?php
/**
 * Asset optimization admin pages.
 *
 * @package Hummingbird\Admin\Pages
 */

namespace Hummingbird\Admin\Pages;

use Hummingbird\Admin\Page;
use Hummingbird\Core\Modules\Minify;
use Hummingbird\Core\Modules\Minify\Minify_Group;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;
use Hummingbird\WP_Hummingbird;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Minification extends Page
 */
class Minification extends Page {

	/**
	 * Display mode.
	 *
	 * @since 1.7.1
	 * @var string $mode  Default: 'basic'. Possible: 'advanced', 'basic'.
	 */
	public $mode = 'basic';

	/**
	 * Function triggered when the page is loaded before render any content.
	 */
	public function on_load() {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_react_scripts' ) );

		$this->setup_navigation();

		$minify_module = Utils::get_module( 'minify' );

		if ( ! $minify_module->scanner->is_scanning() ) {
			$minify_module->scanner->finish_scan();
		}

		if ( ! $minify_module->is_active() ) {
			return;
		}

		$redirect = false;

		// We are here from a performance report - enable advanced mode.
		if ( isset( $_GET['enable-advanced-settings'] ) ) {
			Settings::update_setting( 'view', 'advanced', 'minify' );
			$redirect = true;
		}

		$options = $minify_module->get_options();

		// CDN should be disabled.
		if ( isset( $options['use_cdn'] ) && true === $options['use_cdn'] && ! Utils::is_member() ) {
			$minify_module->toggle_cdn( false );
			$minify_module->clear_cache( false );
		}

		// Re-check files button clicked.
		if ( isset( $_POST['recheck-files'] ) || isset( $_GET['recheck-files'] ) ) { // Input var ok.
			$minify_module->clear_cache( false );

			$collector = $minify_module->sources_collector;
			$collector::clear_collection();

			// Activate minification if is not.
			$minify_module->toggle_service( true );
			$minify_module->scanner->init_scan();
			$redirect = true;
		}

		// Clear cache button clicked.
		if ( isset( $_POST['clear-cache'] ) ) { // Input var okay.
			$minify_module->clear_cache( false );
		}

		// Reset to defaults button clicked on settings page.
		$redirect_url = Utils::get_admin_menu_url( 'minification' );
		if ( isset( $_GET['reset'] ) ) { // Input var okay.
			check_admin_referer( 'wphb-reset-minification' );
			$minify_module->reset_minification_settings();
			$minify_module->clear_cache();

			$redirect_url = add_query_arg( 'recheck-files', true, $redirect_url );
			$redirect     = true;
		}

		// Disable clicked on settings page.
		if ( isset( $_GET['disable'] ) ) { // Input var okay.
			check_admin_referer( 'wphb-disable-minification' );
			$minify_module->disable();
			$redirect = true;
		}

		if ( $redirect ) {
			wp_safe_redirect( $redirect_url );
			exit;
		}
	}

	/**
	 * Enqueue scripts and styles for React.
	 */
	public function enqueue_react_scripts() {
		if ( 'files' !== $this->get_current_tab() ) {
			return;
		}

		wp_enqueue_style( 'wphb-react-minify-styles', WPHB_DIR_URL . 'admin/assets/css/wphb-react-minify.min.css', array(), WPHB_VERSION );
		wp_enqueue_script( 'wphb-react-minify', WPHB_DIR_URL . 'admin/assets/js/wphb-react-minify.min.js', array( 'wp-i18n', 'lodash' ), WPHB_VERSION, true );

		wp_localize_script(
			'wphb-react-minify',
			'wphbReact',
			array(
				'isMember' => Utils::is_member(),
				'links'    => array(
					'wphbDirUrl' => WPHB_DIR_URL,
					'support'    => array(
						'chat'  => Utils::get_link( 'chat' ),
						'forum' => Utils::get_link( 'support' ),
					),
				),
				'nonces'   => array(
					'HBFetchNonce' => wp_create_nonce( 'wphb-fetch' ),
				),
				'module'   => array(
					'isWhiteLabeled' => apply_filters( 'wpmudev_branding_hide_branding', false ),
					'showModal'      => (bool) get_option( 'wphb-minification-show-advanced_modal' ),
				),
			)
		);

		wp_add_inline_script(
			'wphb-react-minify',
			'wp.i18n.setLocaleData( ' . wp_json_encode( Utils::get_locale_data() ) . ', "wphb" );',
			'before'
		);
	}

	/**
	 * Set up navigation for module.
	 *
	 * @since 1.8.2
	 */
	private function setup_navigation() {
		if ( is_multisite() && is_network_admin() ) {
			return;
		}

		$this->tabs = array(
			'files'    => __( 'Assets', 'wphb' ),
			'tools'    => __( 'Tools', 'wphb' ),
			'settings' => __( 'Settings', 'wphb' ),
		);

		$minify = Settings::get_setting( 'enabled', 'minify' );
		if ( is_multisite() && ( ( 'super-admins' === $minify && is_super_admin() ) || ( true === $minify ) ) ) {
			$this->tabs['import'] = __( 'Import / Export', 'wphb' );
		}

		add_filter( 'wphb_admin_after_tab_' . $this->get_slug(), array( $this, 'after_tab' ) );
	}

	/**
	 * Render the template header.
	 */
	public function render_header() {
		// Asset Optimization publish changes.
		if ( isset( $_POST['submit'] ) ) { // Input var okay.
			check_admin_referer( 'wphb-enqueued-files' );

			$options = Utils::get_module( 'minify' )->get_options();
			if ( ! empty( $_POST['styles'] ) ) {
				$options = $this->sanitize_type( 'styles', $options );
			}
			if ( ! empty( $_POST['scripts'] ) ) {
				$options = $this->sanitize_type( 'scripts', $options );
			}

			Utils::get_module( 'minify' )->update_options( $options );

			// Remove notice.
			delete_site_option( 'wphb-notice-minification-optimized-show' );

			// Clear all the page cache.
			do_action( 'wphb_clear_page_cache' );

			$this->admin_notices->show_floating( __( '<strong>Your changes have been published.</strong> Note: Files queued for compression will generate once someone visits your homepage.', 'wphb' ) );
		}

		// Clear cache show notice (from clear cache button and clear cache notice).
		if ( isset( $_POST['clear-cache'] ) ) { // Input var ok.
			$this->admin_notices->show_floating( __( 'Your cache has been successfully cleared. Your assets will regenerate the next time someone visits your website.', 'wphb' ) );
		}

		if ( isset( $_GET['wphb-cache-cleared-with-cloudflare'] ) ) { // Input var ok.
			$this->admin_notices->show_floating( __( 'Your local and Cloudflare caches have been successfully cleared. Your assets will regenerate the next time someone visits your website.', 'wphb' ) );
		}

		add_action( 'wphb_sui_header_sui_actions_right', array( $this, 'add_header_actions' ) );
		add_action( 'wphb_asset_optimization_http2_notice', array( $this, 'render_http2_notice' ) );

		parent::render_header();
	}

	/**
	 * Render upgrade modal.
	 *
	 * @since 2.6.0
	 */
	public function render_modals() {
		if ( ! apply_filters( 'wp_hummingbird_is_active_module_minify', false ) || is_network_admin() ) {
			return;
		}

		if ( ! get_option( 'wphb_do_minification_upgrade' ) ) {
			return;
		}

		$this->modal( 'upgrade-minification' );
		?>
		<script>
			window.addEventListener("load", function(){
				window.SUI.openModal( 'wphb-upgrade-minification-modal', 'wpbody-content', undefined, false );
			});
		</script>
		<?php
	}

	/**
	 * Add content to the header.
	 *
	 * @since 2.5.0
	 */
	public function add_header_actions() {
		if ( ! apply_filters( 'wp_hummingbird_is_active_module_minify', false ) || is_network_admin() ) {
			return;
		}

		if ( ! isset( $this->mode ) || 'advanced' !== $this->mode ) {
			return;
		}
		?>
		<a class="sui-button sui-button-ghost" data-modal-open="wphb-tour-minification-modal" data-modal-open-focus="dialog-close-div" data-modal-mask="true">
			<span class="sui-icon-web-globe-world" aria-hidden="true"></span>
			<?php esc_html_e( 'Take a Tour', 'wphb' ); ?>
		</a>
		<?php
	}

	/**
	 * Show HTTP/2 notice.
	 *
	 * @since 2.6.0
	 */
	public function render_http2_notice() {
		if ( apply_filters( 'wpmudev_branding_hide_branding', false ) ) {
			return;
		}

		if ( ! $this->admin_notices->can_show_notice( 'http2-info' ) ) {
			return;
		}

		if ( Utils::get_module( 'minify' )->scanner->is_scanning() ) {
			return;
		}
		?>
		<div role="alert" class="sui-box sui-summary sui-summary-sm wphb-box-notice <?php echo isset( $_SERVER['WPMUDEV_HOSTED'] ) ? '' : 'wphb-notice-upsell'; ?>" aria-live="assertive">
			<?php $branded_image = apply_filters( 'wpmudev_branding_hero_image', '' ); ?>
			<?php if ( $branded_image ) : ?>
				<div class="sui-summary-image-space" aria-hidden="true" style="background-image: url('<?php echo esc_url( $branded_image ); ?>')"></div>
			<?php else : ?>
				<div class="sui-summary-image-space" aria-hidden="true"></div>
			<?php endif; ?>
			<div class="sui-summary-segment">
				<div class="sui-summary-details sui-no-padding-left">
					<span class="sui-summary-sub sui-no-margin-bottom">
						<?php
						if ( isset( $_SERVER['WPMUDEV_HOSTED'] ) ) {
							esc_attr_e( 'Your server is running the HTTP/2 protocol which automatically optimizes the delivery of your assets for you. You can still combine, and move your files, though this may not always improve performance.', 'wphb' );
						} else {
							printf(
								/* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
								esc_html__( 'Did you know WPMU DEV Hosting runs the HTTP/2 protocol, which automatically optimizes the delivery of your assets for you? Improve your site speed and performance by hosting your site with WPMU DEV. You can learn more about WPMU DEV Hosting %1$shere%2$s.', 'wphb' ),
								'<a href="' . esc_url( \Hummingbird\Core\Utils::get_link( 'hosting', 'AO_hosting_upsell' ) ) . '" target="_blank">',
								'</a>'
							);
						}
						?>
					</span>
					<?php if ( ! isset( $_SERVER['WPMUDEV_HOSTED'] ) ) : ?>
						<a href="<?php echo esc_url( \Hummingbird\Core\Utils::get_link( 'hosting', 'AO_hosting_upsell' ) ); ?>" target="_blank" class="sui-button sui-button-purple" style="margin-top: 10px;">
							<?php esc_html_e( 'Host with us', 'wphb' ); ?>
						</a>
					<?php endif; ?>
				</div>
			</div>
			<div class="wphb-dismiss-icon">
				<a id="wphb-floating-http2-info" class="dismiss" href="#" aria-label="<?php esc_attr_e( 'Dismiss', 'wphb' ); ?>">
					<span class="sui-icon-close" aria-hidden="true"></span>
				</a>
			</div>
		</div>
		<?php
	}

	/**
	 * Register meta boxes.
	 */
	public function register_meta_boxes() {
		if ( is_multisite() && is_network_admin() ) {
			$this->add_meta_box(
				'minification/network-settings',
				__( 'Settings', 'wphb' ),
				array( $this, 'network_settings_meta_box' )
			);

			return;
		}

		/**
		 * Disabled state meta box.
		 */
		$minify_module = Utils::get_module( 'minify' );
		if ( ! $minify_module->is_active() || $minify_module->scanner->is_scanning() ) {
			$this->add_meta_box(
				'minification/empty-files',
				__( 'Get Started', 'wphb' ),
				null,
				null,
				null,
				'box-enqueued-files-empty',
				array(
					'box_content_class' => 'sui-box sui-message',
				)
			);

			return;
		}

		// Move it here from __construct so we don't make an extra db call if minification is disabled.
		$this->mode = Settings::get_setting( 'view', 'minify' );

		/**
		 * Summary meta box.
		 */
		$this->add_meta_box(
			'minification/summary-meta-box',
			null,
			array( $this, 'summary_metabox' ),
			null,
			null,
			'summary',
			array(
				'box_content_class' => 'sui-box sui-summary ' . Utils::get_whitelabel_class(),
			)
		);

		/**
		 * Files meta box.
		 */
		if ( 'advanced' === $this->mode ) {
			$this->add_meta_box(
				'minification/enqueued-files',
				__( 'Assets', 'wphb' ),
				array( $this, 'enqueued_files_metabox' ),
				null,
				null,
				'main',
				array(
					'box_header_class'  => 'sui-box-header box-title-' . $this->mode,
					'box_content_class' => 'no-padding',
				)
			);
		}

		/**
		 * Tools meta box.
		 */
		$this->add_meta_box(
			'minification/tools',
			__( 'Tools', 'wphb' ),
			array( $this, 'tools_metabox' ),
			null,
			null,
			'tools'
		);

		/**
		 * Settings meta box.
		 */
		$this->add_meta_box(
			'minification/settings',
			__( 'Settings', 'wphb' ),
			array( $this, 'settings_metabox' ),
			null,
			null,
			'settings',
			array(
				'box_content_class' => Utils::is_member() ? 'sui-box-body' : 'sui-box-body sui-upsell-items',
			)
		);

		/**
		 * Import/export meta box.
		 *
		 * @since 3.1.1
		 */
		$this->add_meta_box(
			'minification/import',
			__( 'Import / Export', 'wphb' ),
			array( $this, 'import_meta_box' ),
			null,
			null,
			'import'
		);
	}

	/**
	 * *************************
	 * Summary and empty states
	 ***************************/

	/**
	 * Summary meta box.
	 */
	public function summary_metabox() {
		$minify_module = Utils::get_module( 'minify' );
		$collection    = $minify_module->get_resources_collection();

		// Remove those assets that we don't want to display.
		foreach ( $collection['styles'] as $key => $item ) {
			if ( ! apply_filters( 'wphb_minification_display_enqueued_file', true, $item, 'styles' )
				|| ! isset( $item['original_size'], $item['compressed_size'] ) ) {
				unset( $collection['styles'][ $key ] );
			}
		}
		foreach ( $collection['scripts'] as $key => $item ) {
			if ( ! apply_filters( 'wphb_minification_display_enqueued_file', true, $item, 'scripts' )
				|| ! isset( $item['original_size'], $item['compressed_size'] ) ) {
				unset( $collection['scripts'][ $key ] );
			}
		}

		$enqueued_files = count( $collection['scripts'] ) + count( $collection['styles'] );

		$original_size_styles  = Utils::calculate_sum( wp_list_pluck( $collection['styles'], 'original_size' ) );
		$original_size_scripts = Utils::calculate_sum( wp_list_pluck( $collection['scripts'], 'original_size' ) );
		$original_size         = $original_size_scripts + $original_size_styles;

		$compressed_size_styles  = Utils::calculate_sum( wp_list_pluck( $collection['styles'], 'compressed_size' ) );
		$compressed_size_scripts = Utils::calculate_sum( wp_list_pluck( $collection['scripts'], 'compressed_size' ) );
		$compressed_size         = $compressed_size_scripts + $compressed_size_styles;

		if ( (int) $original_size <= 0 ) {
			$percentage = 0;
		} else {
			$percentage = 100 - (int) $compressed_size * 100 / (int) $original_size;
		}
		$percentage      = number_format_i18n( $percentage, 1 );
		$compressed_size = number_format( (float) $original_size - (float) $compressed_size, 0 );

		$use_cdn   = $minify_module->get_cdn_status();
		$is_member = Utils::is_member();

		$args = compact( 'enqueued_files', 'compressed_size', 'percentage', 'use_cdn', 'is_member' );
		$this->view( 'minification/summary-meta-box', $args );
	}

	/**
	 * *************************
	 * Asset Optimization manual
	 *
	 * @since 2.6.0
	 ***************************/

	/**
	 * Enqueued files meta box.
	 *
	 * @since 1.7.1
	 */
	public function enqueued_files_metabox() {
		$module      = Utils::get_module( 'minify' );
		$collection  = $module->get_resources_collection();
		$is_scanning = $module->scanner->is_scanning();

		if ( $is_scanning || ( isset( $collection['scripts'] ) && empty( $collection['scripts'] ) && isset( $collection['styles'] ) && empty( $collection['styles'] ) ) ) {
			$this->view( 'minification/empty-collection-meta-box', array( 'is_scanning' => $module->scanner->is_scanning() ) );

			if ( $is_scanning ) {
				$this->modal( 'check-files' );
			}
			return;
		}

		// Prepare filters.
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			foreach ( get_site_option( 'active_sitewide_plugins', array() ) as $plugin => $item ) {
				$active_plugins[] = $plugin;
			}
		}
		$theme      = wp_get_theme();
		$theme_name = $theme->get( 'Name' );

		$selector_filter                = array();
		$selector_filter[ $theme_name ] = $theme_name;
		foreach ( $active_plugins as $plugin ) {
			if ( ! is_file( WP_PLUGIN_DIR . '/' . $plugin ) ) {
				continue;
			}
			$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
			if ( $plugin_data['Name'] ) {
				// Found plugin, add it as a filter.
				$selector_filter[ $plugin_data['Name'] ] = $plugin_data['Name'];
			}
		}
		$styles_rows  = $this->collection_rows( $collection['styles'], 'styles' );
		$scripts_rows = $this->collection_rows( $collection['scripts'], 'scripts' );
		$others_rows  = $styles_rows['other'];
		$others_rows .= $scripts_rows['other'];

		$this->view(
			'minification/enqueued-files-meta-box',
			array(
				'styles_rows'     => $styles_rows['content'],
				'scripts_rows'    => $scripts_rows['content'],
				'fonts_rows'      => $styles_rows['font'],
				'others_rows'     => $others_rows,
				'selector_filter' => $selector_filter,
				'is_server_error' => $module->errors_controller->is_server_error(),
				'server_errors'   => $module->errors_controller->get_server_errors(),
				'error_time_left' => $module->errors_controller->server_error_time_left(),
				'is_scanning'     => $module->scanner->is_scanning(),
			)
		);
	}

	/**
	 * Tools meta box.
	 *
	 * @since 1.8
	 */
	public function tools_metabox() {
		$this->view(
			'minification/tools-meta-box',
			array(
				'css' => Minify::get_css(),
			)
		);
	}

	/**
	 * Settings meta box.
	 *
	 * @since 1.9
	 */
	public function settings_metabox() {
		$log = WP_Hummingbird::get_instance()->core->logger->get_file( 'minify' );
		if ( ! file_exists( $log ) ) {
			$log = false;
		}

		$path_url = $log;
		if ( $path_url && defined( 'WP_CONTENT_DIR' ) ) {
			$path_url = content_url() . str_replace( WP_CONTENT_DIR, '', $log );
		}

		$this->view(
			'minification/settings-meta-box',
			array(
				'cdn_status'   => Utils::get_module( 'minify' )->get_cdn_status(),
				'cdn_excludes' => Settings::get_setting( 'nocdn', 'minify' ),
				'is_member'    => Utils::is_member(),
				'logging'      => Settings::get_setting( 'log', 'minify' ),
				'file_path'    => Settings::get_setting( 'file_path', 'minify' ),
				'logs_link'    => $log,
				'download_url' => wp_nonce_url(
					add_query_arg(
						array(
							'logs'   => 'download',
							'module' => Utils::get_module( 'minify' )->get_slug(),
						)
					),
					'wphb-log-action'
				),
				'path_url'     => $path_url,
			)
		);
	}

	/**
	 * Content after tabbed menu.
	 *
	 * @param string $tab  Tab name.
	 */
	public function after_tab( $tab ) {
		if ( 'files' === $tab ) {
			echo ' <span class="sui-tag sui-tag-disabled">' . esc_html( Utils::minified_files_count() ) . '</span>';
		}
	}

	/**
	 * Parse settings update.
	 *
	 * @param string $type     Asset type. Accepts: 'scripts' and 'styles'.
	 * @param array  $options  Current settings.
	 *
	 * @return array
	 */
	private function sanitize_type( $type, $options ) {
		$set_asset_options = array(
			'block'        => 'include',
			'dont_minify'  => 'minify',
			'dont_combine' => 'combine',
		);

		$unset_asset_options = array( 'defer', 'inline', 'preload', 'async' );

		$changed_assets = array();

		$collection = Minify\Sources_Collector::get_collection();

		$assets = filter_input( INPUT_POST, $type, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
		foreach ( $assets as $handle => $item ) {
			foreach ( $set_asset_options as $stored_option => $selected_option ) {
				// Make sure we do not force exclude already compressed assets.
				$minified = false;
				if ( 'minify' === $selected_option ) {
					$full_src = $collection[ $type ][ $handle ]['src'];
					$minified = preg_match( '/\.min\.(css|js)/', $full_src );
				}

				$key = array_search( $handle, $options[ $stored_option ][ $type ], true );
				if ( ! isset( $item[ $selected_option ] ) && false === $key && ! $minified ) {
					$options[ $stored_option ][ $type ][] = $handle;
					array_push( $changed_assets, $handle );
				} elseif ( isset( $item[ $selected_option ] ) && false !== $key ) {
					unset( $options[ $stored_option ][ $type ][ $key ] );
					array_push( $changed_assets, $handle );
				}
			}

			foreach ( $unset_asset_options as $option ) {
				$key = array_search( $handle, $options[ $option ][ $type ], true );
				if ( ! isset( $item[ $option ] ) && false !== $key ) {
					unset( $options[ $option ][ $type ][ $key ] );
					array_push( $changed_assets, $handle );
				} elseif ( isset( $item[ $option ] ) && false === $key ) {
					$options[ $option ][ $type ][] = $handle;
					array_push( $changed_assets, $handle );
				}
			}

			/**
			 * This is wrong. Instead of treating the options as "In footer" with values "true" or "false", we
			 * treat this as "Location" with a value of "footer" (will never be header).
			 *
			 * TODO: change this up to match the $unset_asset_options logic above.
			 */
			$key_exists = array_key_exists( $handle, $options['position'][ $type ] );
			if ( isset( $item['position'] ) && 'footer' === $item['position'] && ! $key_exists ) {
				$options['position'][ $type ][ $handle ] = $item['position'];
				array_push( $changed_assets, $handle );
			} elseif ( ! isset( $item['position'] ) && $key_exists ) {
				unset( $options['position'][ $type ][ $handle ] );
				array_push( $changed_assets, $handle );
			}

			// This is a font.
			if ( 'styles' === $type && isset( $item['font-optimize'] ) ) {
				$key = array_search( $handle, $options['fonts'], true );

				// Add new font to optimization array.
				if ( $item['font-optimize'] && false === $key ) {
					array_push( $options['fonts'], $handle );
				}

				// Remove the font from optimization array.
				if ( ! $item['font-optimize'] && false !== $key ) {
					unset( $options['fonts'][ $key ] );
				}
			}
		}

		$changed_assets = array_unique( $changed_assets );
		foreach ( $changed_assets as $asset ) {
			$changed_groups = Minify_Group::get_groups_from_handle( $asset, $type );
			foreach ( $changed_groups as $group ) {
				/**
				 * Delete those groups.
				 *
				 * @var Minify_Group $group
				 */
				$group->delete_file();
			}
		}

		return $options;
	}

	/**
	 * Populate minification table with enqueued files.
	 *
	 * @param array  $collection  Array of files.
	 * @param string $type        Collection type. Accepts: scripts, styles.
	 *
	 * @return array
	 */
	private function collection_rows( $collection, $type ) {
		$minification_module = Utils::get_module( 'minify' );

		$options = $minification_module->get_options();

		// This will be used for filtering.
		$theme          = wp_get_theme();
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			foreach ( get_site_option( 'active_sitewide_plugins', array() ) as $plugin => $item ) {
				$active_plugins[] = $plugin;
			}
		}

		$content = array(
			'content' => '',
			'other'   => '',
			'font'    => '',
		);

		foreach ( $collection as $item ) {
			/**
			 * Filter minification enqueued files items displaying
			 *
			 * @param bool $display If set to true, display the item. Default false
			 * @param array $item Item data
			 * @param string $type Type of the current item (scripts|styles)
			 */
			if ( ! apply_filters( 'wphb_minification_display_enqueued_file', true, $item, $type ) ) {
				continue;
			}

			$position = '';
			if ( ! empty( $options['position'][ $type ][ $item['handle'] ] ) && in_array(
				$options['position'][ $type ][ $item['handle'] ],
				array(
					'header',
					'footer',
				),
				true
			) ) {
				$position = $options['position'][ $type ][ $item['handle'] ];
			}

			$base_name       = $type . '[' . $item['handle'] . ']';
			$compressed_size = isset( $item['compressed_size'] ) ? $item['compressed_size'] : false;
			$original_size   = false;

			if ( isset( $item['original_size'] ) ) {
				$original_size = $item['original_size'];
			} elseif ( file_exists( Utils::src_to_path( $item['src'] ) ) ) {
				// Get original file size for local files that don't have it set for some reason.
				$original_size = number_format_i18n( filesize( Utils::src_to_path( $item['src'] ) ) / 1000, 1 );
			}

			$processed  = ( false !== $original_size ) && ( false !== $compressed_size );
			$compressed = $processed && ( $compressed_size < $original_size );

			$site_url = str_replace( array( 'http://', 'https://' ), '', get_option( 'siteurl' ) );
			$rel_src  = str_replace( array( 'http://', 'https://', $site_url ), '', $item['src'] );
			$rel_src  = ltrim( $rel_src, '/' );
			$full_src = $item['src'];

			$info = pathinfo( $full_src );

			$optimized = false; // This is only relevant for fonts.

			$ext = 'OTHER';
			if ( isset( $info['dirname'] ) && false !== strpos( $info['dirname'], 'fonts.googleapis.com' ) ) {
				$ext       = 'FONT';
				$optimized = in_array( $item['handle'], $options['fonts'], true );
			} elseif ( isset( $info['extension'] ) && preg_match( '/(css)\??[a-zA-Z=0-9]*/', $info['extension'] ) ) {
				$ext = 'CSS';
			} elseif ( isset( $info['extension'] ) && preg_match( '/(js)\??[a-zA-Z=0-9]*/', $info['extension'] ) ) {
				$ext = 'JS';
			}

			$row_error         = $minification_module->errors_controller->get_handle_error( $item['handle'], $type );
			$disable_switchers = $row_error ? $row_error['disable'] : array();

			$filter    = '';
			$component = '';
			if ( preg_match( '/wp-content\/themes\/(.*)\//', $full_src, $matches ) ) {
				$filter    = $theme->get( 'Name' );
				$component = 'theme';
			} elseif ( preg_match( '/wp-content\/plugins\/([\w\-_]*)\//', $full_src, $matches ) ) {
				// The source comes from a plugin.
				foreach ( $active_plugins as $active_plugin ) {
					if ( stristr( $active_plugin, $matches[1] ) ) {
						// It seems that we found the plguin but let's double check.
						$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $active_plugin );
						if ( $plugin_data['Name'] ) {
							// Found plugin, add it as a filter.
							$filter = $plugin_data['Name'];
						}
						break;
					}
				}
				$component = 'plugin';
			}

			$minified_file = preg_match( '/\.min\.(css|js)/', wp_basename( $rel_src ) );

			/**
			 * Allows to enable/disable switchers in minification page
			 *
			 * @param array $disable_switchers List of switchers disabled for an item ( include, minify, combine)
			 * @param array $item Info about the current item
			 * @param string $type Type of the current item (scripts|styles)
			 */
			$disable_switchers = apply_filters( 'wphb_minification_disable_switchers', $disable_switchers, $item, $type );

			// Disabled state filter.
			$disabled = in_array( $item['handle'], $options['block'][ $type ], true );

			// Check if file has had changes made to it (don't need to check minify).
			$file_changed = false;
			if ( ! in_array( $item['handle'], $options['dont_combine'][ $type ], true )
				|| 'footer' === $position
				|| in_array( $item['handle'], $options['defer'][ $type ], true )
				|| in_array( $item['handle'], $options['inline'][ $type ], true )
				|| in_array( $item['handle'], $options['preload'][ $type ], true )
				|| in_array( $item['handle'], $options['async'][ $type ], true )
			) {
				$file_changed = true;
			}

			$is_local = Minify_Group::is_src_local( $full_src );

			$args = compact(
				'item',
				'options',
				'type',
				'position',
				'base_name',
				'original_size',
				'compressed_size',
				'rel_src',
				'full_src',
				'ext',
				'row_error',
				'disable_switchers',
				'filter',
				'minified_file',
				'disabled',
				'processed',
				'compressed',
				'file_changed',
				'component',
				'is_local',
				'optimized'
			);

			if ( 'OTHER' !== $ext && 'FONT' !== $ext ) {
				$content['content'] .= $this->view( 'minification/advanced-files-rows', $args, false );
			} elseif ( 'FONT' === $ext ) {
				$content['font'] .= $this->view( 'minification/advanced-fonts-rows', $args, false );
			} else {
				$content['other'] .= $this->view( 'minification/advanced-files-rows', $args, false );
			}
		}

		return $content;
	}

	/**
	 * Network settings meta box.
	 *
	 * @since 2.0.0
	 */
	public function network_settings_meta_box() {
		$minify  = Utils::get_module( 'minify' );
		$options = $minify->get_options();

		$is_member = Utils::is_member();

		$enabled = 'super-admins' === $options['enabled'] || $options['enabled'];

		$this->view(
			'minification/network-settings-meta-box',
			array(
				'download_url'     => wp_nonce_url(
					add_query_arg(
						array(
							'logs'   => 'download',
							'module' => Utils::get_module( 'minify' )->get_slug(),
						)
					),
					'wphb-log-action'
				),
				'enabled'          => $enabled,
				'is_member'        => $is_member,
				'log_enabled'      => $options['log'],
				'type'             => $enabled ? $options['enabled'] : 'super-admins',
				'use_cdn'          => $minify->get_cdn_status(),
				'use_cdn_disabled' => ! $is_member || ! $options['enabled'],
			)
		);
	}

	/**
	 * Import/export meta box. Shown on subsites.
	 *
	 * @since 3.1.1
	 */
	public function import_meta_box() {
		$this->view( 'settings/import-export-meta-box' );
	}

}
