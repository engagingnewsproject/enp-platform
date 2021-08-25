<?php
/**
 * Dashboard page.
 *
 * @package Hummingbird
 */

namespace Hummingbird\Admin\Pages;

use Hummingbird\Admin\Page;
use Hummingbird\Core\Configs;
use Hummingbird\Core\Modules\Advanced;
use Hummingbird\Core\Modules\Performance;
use Hummingbird\Core\Modules\Uptime;
use Hummingbird\Core\Utils;
use stdClass;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Dashboard
 *
 * @package Hummingbird\Admin\Pages
 */
class Dashboard extends Page {

	use \Hummingbird\Core\Traits\Smush;

	/**
	 * Uptime report.
	 *
	 * @since 1.7.0
	 *
	 * @var   array|stdClass|WP_Error $uptime_report
	 */
	private $uptime_report = array();

	/**
	 * Uptime status.
	 *
	 * @since 1.8.1
	 *
	 * @var bool $uptime_active
	 */
	private $uptime_active = false;

	/**
	 * Gzip status.
	 *
	 * @since 1.8.1
	 *
	 * @var array $gzip_status
	 */
	private $gzip_status = array();

	/**
	 * Caching status.
	 *
	 * @since 1.8.1
	 *
	 * @var array $caching_status
	 */
	private $caching_status = array();

	/**
	 * Performance report data.
	 *
	 * @var stdClass $performance
	 */
	private $performance;

	/**
	 * Init page variables.
	 *
	 * @since 1.8.1
	 */
	private function init() {
		$module = Utils::get_module( 'gzip' );
		$module->get_analysis_data();
		$this->gzip_status = $module->status;

		$module = Utils::get_module( 'caching' );
		$module->get_analysis_data();
		$this->caching_status = $module->status;

		$uptime_module       = Utils::get_module( 'uptime' );
		$this->uptime_active = $uptime_module->is_active();
		if ( $this->uptime_active ) {
			$this->uptime_report = $uptime_module->get_last_report();
		}

		$this->performance              = new stdClass();
		$this->performance->last_report = Performance::get_last_report();

		$this->performance->report_dismissed = Performance::report_dismissed();
		$this->performance->is_doing_report  = Performance::is_doing_report();

		$selected_type = filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING );
		$this->performance->type = 'mobile' === $selected_type ? 'mobile' : 'desktop';
	}

	/**
	 * Function triggered when the page is loaded before render any content.
	 */
	public function on_load() {
		add_action( 'admin_enqueue_scripts', array( new Configs(), 'enqueue_react_scripts' ) );

		if ( is_multisite() && ! is_network_admin() ) {
			$minify_module = Utils::get_module( 'minify' );

			if ( ! $minify_module->scanner->is_scanning() ) {
				$minify_module->scanner->finish_scan();
			}
		}

		if ( isset( $_GET['wphb-clear-files'] ) ) { // Input var ok.
			check_admin_referer( 'wphb-clear-files' );

			$modules   = Utils::get_active_cache_modules();
			$url       = remove_query_arg( array( 'wphb-clear-files', 'updated', '_wpnonce' ) );
			$query_arg = 'wphb-cache-cleared';

			foreach ( $modules as $module => $name ) {
				$mod = Utils::get_module( $module );

				if ( $mod->is_active() ) {
					if ( 'minify' === $module ) {
						$mod->clear_files();
					} else {
						$mod->clear_cache();
					}

					if ( 'cloudflare' === $module ) {
						$query_arg = 'wphb-cache-cleared-with-cloudflare';
					}
				}
			}

			wp_safe_redirect( add_query_arg( $query_arg, 'true', $url ) );
			exit;
		}

		if ( isset( $_GET['run'] ) && isset( $_GET['type'] ) ) { // Input var ok.
			$this->run_actions( wp_unslash( $_GET['type'] ) ); // Input var ok.
		}
	}

	/**
	 * Overwrites parent class render_header method.
	 *
	 * Renders the template header that is repeated on every page.
	 * From WPMU DEV Dashboard
	 */
	public function render_header() {
		if ( filter_input( INPUT_GET, 'wphb-cache-cleared' ) ) {
			$this->admin_notices->show_floating( __( 'Your cache has been successfully cleared. Your assets will regenerate the next time someone visits your website.', 'wphb' ) );
		}

		if ( filter_input( INPUT_GET, 'wphb-cache-cleared-with-cloudflare' ) ) {
			$this->admin_notices->show_floating( __( 'Your local and Cloudflare caches have been successfully cleared. Your assets will regenerate the next time someone visits your website.', 'wphb' ) );
		}

		add_action( 'wphb_sui_header_sui_actions_right', array( $this, 'add_header_actions' ) );

		parent::render_header();
	}

	/**
	 * Add content to the header.
	 *
	 * @since 2.5.0
	 */
	public function add_header_actions() {
		$modules = Utils::get_active_cache_modules();
		if ( count( $modules ) <= 0 ) {
			return;
		}

		add_filter( 'wphb_active_cache_modules', array( $this, 'maybe_add_cache_module' ) );
		?>
		<button type="button" class="sui-button sui-tooltip sui-tooltip-bottom-right sui-tooltip-constrained"
			data-tooltip="<?php esc_attr_e( 'Clear all active cache types from one place.', 'wphb' ); ?>"
			data-modal-open="clear-cache-modal" data-modal-open-focus="clear-cache-modal-button">
			<?php esc_html_e( 'Clear Cache', 'wphb' ); ?>
		</button>
		<?php
	}

	/**
	 * Make sure we are adding the missing modules for page cache.
	 *
	 * @since 2.7.1
	 *
	 * @param array $modules  List of active modules.
	 */
	public function maybe_add_cache_module( $modules ) {
		if ( ! isset( $modules['page_cache'] ) ) {
			return $modules;
		}

		$options = Utils::get_module( 'page_cache' )->get_options();

		if ( ! isset( $options['integrations'] ) || empty( $options['integrations'] ) ) {
			return $modules;
		}

		if ( isset( $options['integrations']['varnish'] ) && $options['integrations']['varnish'] ) {
			$modules['varnish'] = __( 'Varnish Cache', 'wphb' );
		}

		if ( isset( $options['integrations']['opcache'] ) && $options['integrations']['opcache'] ) {
			$modules['opcache'] = __( 'OpCache', 'wphb' );
		}

		return $modules;
	}

	/**
	 * Run Performance, Asset optimization, Uptime...
	 *
	 * @param string $type  Action type.
	 */
	private function run_actions( $type ) {
		check_admin_referer( 'wphb-run-dashboard' );

		$uptime = Utils::get_module( 'uptime' );

		// Check if Uptime is active in the server.
		if ( Uptime::is_remotely_enabled() ) {
			$uptime->enable_locally();
		} else {
			$uptime->disable_locally();
		}

		// Start performance or asset optimization scam.
		if ( 'performance' === $type || 'minification' === $type ) {
			$module = $type;
			if ( 'minification' === $type ) {
				$module = 'minify';
			}

			Utils::get_module( $module )->init_scan();
			wp_safe_redirect( remove_query_arg( array( 'run', '_wpnonce' ), Utils::get_admin_menu_url( $type ) ) );
			exit;
		} elseif ( 'uptime' === $type ) {
			// Uptime reports.
			$uptime->get_last_report( 'week', true );
			wp_safe_redirect( remove_query_arg( array( 'run', '_wpnonce' ) ) );
			exit;
		}
	}

	/**
	 * Register available metaboxes on the Dashboard page.
	 */
	public function register_meta_boxes() {
		$this->init();

		/**
		 * Summary meta box.
		 */
		$metabox = ! is_multisite() || is_network_admin() ? 'dashboard_welcome_metabox' : 'dashboard_network_summary_metabox';
		$this->add_meta_box(
			'dashboard/welcome',
			null,
			array( $this, $metabox ),
			null,
			null,
			'main',
			array(
				'box_class'         => 'sui-box sui-summary ' . Utils::get_whitelabel_class(),
				'box_content_class' => false,
			)
		);

		/**
		 * Performance report meta boxes.
		 */
		if ( $this->performance->is_doing_report ) {
			$this->add_meta_box(
				'dashboard/performance/running-test',
				__( 'Performance test in progress', 'wphb' ),
				null,
				null,
				null,
				'box-dashboard-left'
			);
		} elseif ( is_wp_error( $this->performance->last_report ) || ( isset( $this->performance->last_report->data ) && is_null( $this->performance->last_report->data->{$this->performance->type}->metrics ) ) ) {
			$this->add_meta_box(
				'dashboard-performance-module-error',
				__( 'Performance Report', 'wphb' ),
				array( $this, 'dashboard_performance_module_error_metabox' ),
				null,
				null,
				'box-dashboard-left'
			);
		} elseif ( ! $this->performance->is_doing_report && $this->performance->last_report && ! $this->performance->report_dismissed ) {
			$options = Utils::get_module( 'performance' )->get_options();
			if ( ! is_multisite() || is_network_admin() || ( $options['subsite_tests'] && is_super_admin() ) || ( ! is_network_admin() && true === $options['subsite_tests'] ) ) {
				$this->add_meta_box(
					'dashboard-performance-module',
					__( 'Performance Report', 'wphb' ),
					array( $this, 'dashboard_performance_module_metabox' ),
					array( $this, 'dashboard_performance_module_metabox_header' ),
					array( $this, 'dashboard_performance_module_metabox_footer' ),
					'box-dashboard-left',
					array(
						'box_content_class' => false,
					)
				);
			}
		} elseif ( $this->performance->report_dismissed ) {
			$this->add_meta_box(
				'dashboard-performance-module',
				__( 'Performance Report', 'wphb' ),
				array( $this, 'dashboard_performance_module_metabox_dismissed' ),
				array( $this, 'dashboard_performance_module_metabox_header' ),
				array( $this, 'dashboard_performance_module_metabox_footer' ),
				'box-dashboard-left'
			);
		} else {
			$this->add_meta_box(
				'dashboard-performance-disabled',
				__( 'Performance Report', 'wphb' ),
				array( $this, 'dashboard_performance_disabled_metabox' ),
				null,
				null,
				'box-dashboard-left'
			);
		}

		/**
		 * Up-sell meta box.
		 */
		if ( ! Utils::is_member() ) {
			$this->add_meta_box(
				'dashboard/welcome/upsell',
				__( 'Hummingbird Pro', 'wphb' ),
				null,
				null,
				null,
				'box-dashboard-right'
			);
		}

		/**
		 * Page caching meta boxes.
		 */
		$module  = Utils::get_module( 'page_cache' );
		$options = $module->get_options();
		if ( ! is_multisite() || is_network_admin() || ( $options['enabled'] && is_super_admin() ) || ( ! is_network_admin() && 'blog-admins' === $options['enabled'] ) ) {
			$footer = $module->is_active() ? array( $this, 'dashboard_page_caching_module_metabox_footer' ) : null;
			$this->add_meta_box(
				'dashboard-caching-page-module',
				__( 'Page Caching', 'wphb' ),
				array( $this, 'dashboard_page_caching_module_metabox' ),
				null,
				$footer,
				'box-dashboard-left'
			);
		}

		if ( ! is_multisite() || is_network_admin() ) {
			/**
			 * Browser caching.
			 */
			$browser_caching_args = array();

			$cf_module = Utils::get_module( 'cloudflare' );
			if ( ! ( $cf_module->is_connected() && $cf_module->is_zone_selected() ) ) {
				if ( ! get_site_option( 'wphb-cloudflare-dash-notice' ) && 'dismissed' !== get_site_option( 'wphb-cloudflare-dash-notice' ) ) {
					$browser_caching_args = array(
						'box_content_class' => 'sui-box-body sui-upsell-items',
					);
				}
			}

			$this->add_meta_box(
				'dashboard-browser-caching-module',
				__( 'Browser Caching', 'wphb' ),
				array( $this, 'dashboard_browser_caching_module_metabox' ),
				array( $this, 'dashboard_browser_caching_module_metabox_header' ),
				array( $this, 'dashboard_browser_caching_module_metabox_footer' ),
				'box-dashboard-left',
				$browser_caching_args
			);

			/**
			 * Gravatar caching
			 */
			$footer = null;
			if ( Utils::get_module( 'gravatar' )->is_active() ) {
				$footer = array( $this, 'dashboard_gravatar_caching_module_metabox_footer' );
			}
			$this->add_meta_box(
				'dashboard-caching-gravatar-module',
				__( 'Gravatar Caching', 'wphb' ),
				array( $this, 'dashboard_gravatar_caching_module_metabox' ),
				null,
				$footer,
				'box-dashboard-left'
			);

			/**
			 * GZIP
			 */
			$this->add_meta_box(
				'dashboard-gzip-module',
				__( 'GZIP Compression', 'wphb' ),
				array( $this, 'dashboard_gzip_module_metabox' ),
				array( $this, 'dashboard_gzip_module_metabox_header' ),
				array( $this, 'dashboard_gzip_module_metabox_footer' ),
				'box-dashboard-right',
				array(
					'box_footer_class' => 'sui-box-footer sui-pull-up',
				)
			);
		}

		/**
		 * Asset Optimization
		 */
		if ( is_multisite() && is_network_admin() ) {
			// Asset optimization metabox is different on network admin.
			$this->add_meta_box(
				'dashboard/minification/network-module',
				__( 'Asset Optimization', 'wphb' ),
				array( $this, 'dashboard_minification_network_module_metabox' ),
				null,
				array( $this, 'dashboard_minification_module_metabox_footer' ),
				'box-dashboard-right'
			);
		} else {
			$module  = Utils::get_module( 'minify' );
			$options = $module->get_options();

			if ( ! is_multisite() || is_network_admin() || ( $options['enabled'] && is_super_admin() ) || ( ! is_network_admin() && true === $options['enabled'] ) ) {
				$content    = is_multisite() && ! is_main_site() && 1 === count( $this->meta_boxes['wphb'] ) ? 'box-dashboard-left' : 'box-dashboard-right';
				$collection = $module->get_resources_collection();
				if ( ( ! empty( $collection['styles'] ) || ! empty( $collection['scripts'] ) ) && ( $module->is_active() ) ) {
					$this->add_meta_box(
						'dashboard/minification-module',
						__( 'Asset Optimization', 'wphb' ),
						array( $this, 'dashboard_minification_module_metabox' ),
						null,
						array( $this, 'dashboard_minification_module_metabox_footer' ),
						$content
					);
				} else {
					$this->add_meta_box(
						'dashboard/minification-disabled',
						__( 'Asset Optimization', 'wphb' ),
						array( $this, 'dashboard_minification_disabled_metabox' ),
						null,
						null,
						$content
					);
				}
			}
		}

		/* Advanced tools */
		$content = is_multisite() && ! is_main_site() && 1 === count( $this->meta_boxes['wphb'] ) ? 'box-dashboard-left' : 'box-dashboard-right';
		$this->add_meta_box(
			'dashboard/advanced-tools',
			__( 'Advanced Tools', 'wphb' ),
			array( $this, 'dashboard_advanced_metabox' ),
			null,
			array( $this, 'dashboard_advanced_metabox_footer' ),
			$content,
			array(
				'box_footer_class' => 'sui-box-footer sui-pull-up',
			)
		);

		/* Smush */
		if ( is_main_site() || is_network_admin() || ( is_multisite() && $this->is_smush_enabled() && get_site_option( 'wp-smush-networkwide' ) ) ) {
			$smush_id     = Utils::is_member() ? 'dashboard-smush' : 'dashboard/smush/no-membership';
			$smush_footer = array( $this, 'dashboard_smush_metabox_footer' );
			if ( ! $this->is_smush_installed() || ! $this->is_smush_enabled() || ! $this->is_smush_configurable() ) {
				$smush_footer = null;
			}
			$box_content_class = Utils::is_member() ? 'sui-box-body' : 'sui-box-body sui-upsell-items';

			$this->add_meta_box(
				$smush_id,
				__( 'Image Optimization', 'wphb' ),
				array( $this, 'dashboard_smush_metabox' ),
				array( $this, 'dashboard_smush_metabox_header' ),
				$smush_footer,
				'box-dashboard-left',
				array(
					'box_content_class' => $box_content_class,
				)
			);
		}

		if ( is_multisite() && ! is_network_admin() ) {
			return;
		}

		/* Uptime */
		if ( ! Utils::is_member() ) {
			$this->add_meta_box(
				'dashboard/uptime/no-membership',
				__( 'Uptime Monitoring', 'wphb' ),
				null,
				array( $this, 'dashboard_uptime_module_metabox_header' ),
				null,
				'box-dashboard-right',
				array(
					'box_content_class' => 'sui-box-body sui-upsell-items',
				)
			);
		} elseif ( is_wp_error( $this->uptime_report ) && $this->uptime_active ) {
			$this->add_meta_box(
				'dashboard-uptime-error',
				__( 'Uptime Monitoring', 'wphb' ),
				array( $this, 'dashboard_uptime_error_metabox' ),
				null,
				null,
				'box-dashboard-right'
			);
		} elseif ( ! $this->uptime_active ) {
			$this->add_meta_box(
				'dashboard-uptime-disabled',
				__( 'Uptime Monitoring', 'wphb' ),
				array( $this, 'dashboard_uptime_disabled_metabox' ),
				null,
				null,
				'box-dashboard-right'
			);
		} else {
			$this->add_meta_box(
				'dashboard-uptime',
				__( 'Uptime Monitoring', 'wphb' ),
				array( $this, 'dashboard_uptime_metabox' ),
				array( $this, 'dashboard_uptime_module_metabox_header' ),
				array( $this, 'dashboard_uptime_module_metabox_footer' ),
				'box-dashboard-right',
				array(
					'box_footer_class' => 'sui-box-footer sui-pull-up',
				)
			);
		}

		/* Reports */
		if ( ! Utils::is_member() || ( defined( 'WPHB_WPORG' ) && WPHB_WPORG ) ) {
			$this->add_meta_box(
				'dashboard/reports/no-membership',
				__( 'Reports', 'wphb' ),
				null,
				array( $this, 'dashboard_reports_module_metabox_header' ),
				null,
				'box-dashboard-right',
				array(
					'box_content_class' => 'sui-box-body sui-upsell-items',
				)
			);
		}
	}

	/**
	 * Display dashboard welcome metabox.
	 */
	public function dashboard_welcome_metabox() {
		$site_date = $cf_current = '';
		$cf_active = false;

		if ( Utils::is_member() && isset( $this->uptime_report->up_since ) && false !== $this->uptime_report->up_since ) {
			$gmt_date  = date( 'Y-m-d H:i:s', $this->uptime_report->up_since );
			$site_date = get_date_from_gmt( $gmt_date, get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) );
		}

		$cf_module = Utils::get_module( 'cloudflare' );
		if ( $cf_module->is_connected() && $cf_module->is_zone_selected() ) {
			$cf_active  = true;
			$cf_current = $cf_module->get_caching_expiration();
			if ( is_wp_error( $cf_current ) ) {
				$cf_current = '';
			}
		}

		$this->view(
			'dashboard/welcome/meta-box',
			array(
				'caching_status'   => $this->caching_status,
				'caching_issues'   => Utils::get_number_of_issues( 'caching', $this->caching_status ),
				'gzip_status'      => $this->gzip_status,
				'gzip_issues'      => Utils::get_number_of_issues( 'gzip', $this->gzip_status ),
				'uptime_active'    => $this->uptime_active,
				'uptime_report'    => $this->uptime_report,
				'report_type'      => $this->performance->type,
				'last_report'      => isset( $this->performance->last_report->data ) ? $this->performance->last_report->data : false,
				'report_dismissed' => $this->performance->report_dismissed,
				'is_doing_report'  => $this->performance->is_doing_report,
				'cf_active'        => $cf_active,
				'cf_current'       => $cf_current,
				'site_date'        => $site_date,
			)
		);
	}

	/**
	 * Dashboard welcome metabox header.
	 */
	public function dashboard_welcome_metabox_header() {
		/* Translators: %s: username */
		$title = sprintf( __( 'Welcome %s', 'wphb' ), Utils::get_current_user_name() );
		$this->view( 'dashboard/welcome/meta-box-header', compact( 'title' ) );
	}

	/**
	 * Dashboard summary meta box.
	 *
	 * @since 2.0.0
	 */
	public function dashboard_network_summary_metabox() {
		$db_items = Advanced::get_db_count();
		$options  = Utils::get_module( 'minify' )->get_options();

		$is_active = Utils::get_module( 'page_cache' )->is_active();
		if ( 'blog-admins' === $is_active ) {
			$is_active = true;
		}

		$this->view(
			'dashboard/welcome/subsite-meta-box',
			array(
				'caching_enabled'  => $is_active,
				'database_items'   => $db_items->total,
				'is_doing_report'  => $this->performance->is_doing_report,
				'last_report'      => isset( $this->performance->last_report->data ) ? $this->performance->last_report->data : false,
				'minify_enabled'   => $options['enabled'] && $options['minify_blog'],
				'report_dismissed' => $this->performance->report_dismissed,
				'report_type'      => $this->performance->type,
			)
		);
	}

	/**
	 * *************************
	 * CACHING
	 ***************************/

	/**
	 * Display browser caching metabox.
	 */
	public function dashboard_browser_caching_module_metabox() {
		$caching        = Utils::get_module( 'caching' );
		$caching_status = $this->caching_status;
		$recommended    = $caching->get_recommended_caching_values();
		$expiration     = 0;
		// Get expiration setting values.
		$options = $caching->get_options();
		$expires = array(
			'css'        => $options['expiry_css'],
			'javascript' => $options['expiry_javascript'],
			'media'      => $options['expiry_media'],
			'images'     => $options['expiry_images'],
		);

		$cf_module = Utils::get_module( 'cloudflare' );

		$show_cf_notice = false;
		$cf_current     = $cf_current_human = $cf_tooltip = '';
		$cf_active      = $cf_module->is_connected() && $cf_module->is_zone_selected();
		$cf_server      = $cf_module->has_cloudflare();

		if ( $cf_active ) {
			$expiration = $cf_current = $cf_module->get_caching_expiration();

			if ( is_wp_error( $cf_current ) ) {
				$cf_current = '';
			}

			// Fill the report with values from Cloudflare.
			$caching_status = array_fill_keys( array_keys( $expires ), $expiration );
			// Save status.
			$cf_server = $cf_module->has_cloudflare();

			$cf_tooltip       = YEAR_IN_SECONDS === $cf_current ? __( 'Caching is enabled', 'wphb' ) : __( "Caching is enabled but you aren't using our recommended value", 'wphb' );
			$cf_current_human = Utils::human_read_time_diff( $cf_current );
		} elseif ( ! get_site_option( 'wphb-cloudflare-dash-notice' ) && 'dismissed' !== get_site_option( 'wphb-cloudflare-dash-notice' ) ) {
			$show_cf_notice = true;
		}
		$cf_notice = $cf_server ? __( 'We’ve detected you’re using Cloudflare!', 'wphb' ) : __( 'Using Cloudflare?', 'wphb' );

		// Get number of issues for notification box.
		$issues = 0;
		if ( ! $cf_active ) {
			$issues = Utils::get_number_of_issues( 'caching', $caching_status );
		} elseif ( YEAR_IN_SECONDS > $expiration ) {
			$issues = count( $caching_status );
			// Add an issue for the Cloudflare type.
			$issues++;
		}
		$human_results = array_map( array( 'Hummingbird\\Core\\Utils', 'human_read_time_diff' ), $caching_status );

		$args = array(
			'results'               => $caching_status,
			'recommended'           => $recommended,
			'human_results'         => $human_results,
			'cf_tooltip'            => $cf_tooltip,
			'cf_current'            => $cf_current,
			'cf_current_human'      => $cf_current_human,
			'cf_active'             => $cf_active,
			'issues'                => $issues,
			'cf_notice'             => $cf_notice,
			'show_cf_notice'        => $show_cf_notice,
			'cf_connect_url'        => Utils::get_admin_menu_url( 'caching' ) . '&view=caching&connect-cloudflare=true#connect-cloudflare',
			'caching_type_tooltips' => $caching->get_types(),
			'configure_caching_url' => Utils::get_admin_menu_url( 'caching' ) . '&view=caching#wphb-box-caching-settings',
		);
		if ( $cf_active ) {
			$this->view( 'dashboard/caching/cloudflare-module-meta-box', $args );
		} else {
			$this->view( 'dashboard/caching/module-meta-box', $args );
		}
	}

	/**
	 * Display browser caching metabox header.
	 */
	public function dashboard_browser_caching_module_metabox_header() {
		$title  = __( 'Browser Caching', 'wphb' );
		$issues = Utils::get_number_of_issues( 'caching', $this->caching_status );

		$cf_module = Utils::get_module( 'cloudflare' );
		$cf_active = false;

		$cf_current = '';
		if ( $cf_module->is_connected() && $cf_module->is_zone_selected() ) {
			$cf_active  = true;
			$cf_current = $cf_module->get_caching_expiration();
			if ( is_wp_error( $cf_current ) ) {
				$cf_current = '';
			}
		}

		$args = compact( 'title', 'issues', 'cf_active', 'cf_current' );
		$this->view( 'dashboard/caching/module-meta-box-header', $args );
	}

	/**
	 * Display browser caching metabox footer.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_browser_caching_module_metabox_footer() {
		$cf_module = Utils::get_module( 'cloudflare' );
		$this->view(
			'dashboard/caching/module-meta-box-footer',
			array(
				'caching_url' => Utils::get_admin_menu_url( 'caching' ) . '&view=caching',
				'cf_active'   => $cf_module->is_connected() && $cf_module->is_zone_selected(),
			)
		);
	}

	/**
	 * Display page caching metabox.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_page_caching_module_metabox() {
		$activate_url = add_query_arg(
			array(
				'action' => 'enable',
				'module' => 'page_cache',
			),
			Utils::get_admin_menu_url( 'caching' )
		);
		$activate_url = wp_nonce_url( $activate_url, 'wphb-caching-actions' );

		$is_active = Utils::get_module( 'page_cache' )->is_active();

		if ( 'blog-admins' === $is_active ) {
			$is_active = true;
		}

		$this->view( 'dashboard/caching/page-caching-module-meta-box', compact( 'is_active', 'activate_url' ) );
	}

	/**
	 * Page caching meta box footer.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_page_caching_module_metabox_footer() {
		$url = Utils::get_admin_menu_url( 'caching' ) . '&view=main';
		$this->view( 'dashboard/caching/page-caching-module-meta-box-footer', compact( 'url' ) );
	}

	/**
	 * Display gravatar caching meta box.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_gravatar_caching_module_metabox() {
		$activate_url = add_query_arg(
			array(
				'action' => 'enable',
				'module' => 'gravatar',
				'view'   => 'gravatar',
			),
			Utils::get_admin_menu_url( 'caching' )
		);
		$activate_url = wp_nonce_url( $activate_url, 'wphb-caching-actions' );

		$is_active = Utils::get_module( 'gravatar' )->is_active();

		$this->view( 'dashboard/caching/gravatar-module-meta-box', compact( 'is_active', 'activate_url' ) );
	}

	/**
	 * Display gravatar caching meta box footer.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_gravatar_caching_module_metabox_footer() {
		$url = Utils::get_admin_menu_url( 'caching' ) . '&view=gravatar';
		$this->view( 'dashboard/caching/gravatar-module-meta-box-footer', compact( 'url' ) );
	}

	/**
	 * *************************
	 * UPTIME
	 ***************************/

	/**
	 * Uptime meta box.
	 */
	public function dashboard_uptime_metabox() {
		$uptime_stats = $this->uptime_report;
		$this->view( 'dashboard/uptime/module-meta-box', compact( 'uptime_stats' ) );
	}

	/**
	 * Uptime header meta box.
	 */
	public function dashboard_uptime_module_metabox_header() {
		$title = __( 'Uptime Monitoring', 'wphb' );
		$this->view( 'dashboard/uptime/module-meta-box-header', compact( 'title' ) );
	}

	/**
	 * Uptime footer meta box.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_uptime_module_metabox_footer() {
		$url = Utils::get_admin_menu_url( 'uptime' );
		$this->view( 'dashboard/uptime/module-meta-box-footer', compact( 'url' ) );
	}

	/**
	 * Uptime disabled meta box.
	 */
	public function dashboard_uptime_disabled_metabox() {
		$enable_url = add_query_arg( 'action', 'enable', Utils::get_admin_menu_url( 'uptime' ) );
		$enable_url = wp_nonce_url( $enable_url, 'wphb-toggle-uptime' );
		$this->view( 'dashboard/uptime/disabled-meta-box', compact( 'enable_url' ) );
	}

	/**
	 * Uptime error meta box.
	 */
	public function dashboard_uptime_error_metabox() {
		$report      = $this->uptime_report;
		$retry_url   = add_query_arg(
			array(
				'run'  => 'true',
				'type' => 'uptime',
			),
			Utils::get_admin_menu_url()
		);
		$retry_url   = wp_nonce_url( $retry_url, 'wphb-run-dashboard' ) . '#wphb-box-dashboard-uptime-module';
		$support_url = Utils::get_link( 'support' );
		$error       = $report->get_error_message();

		$this->view( 'dashboard/uptime/error-meta-box', compact( 'retry_url', 'support_url', 'error' ) );
	}

	/**
	 * *************************
	 * ASSET OPTIMIZATION
	 ***************************/

	/**
	 * Asset optimization meta box.
	 */
	public function dashboard_minification_module_metabox() {
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

		$original_size = $original_size_scripts + $original_size_styles;

		$compressed_size_styles  = Utils::calculate_sum( wp_list_pluck( $collection['styles'], 'compressed_size' ) );
		$compressed_size_scripts = Utils::calculate_sum( wp_list_pluck( $collection['scripts'], 'compressed_size' ) );
		$compressed_size         = $compressed_size_scripts + $compressed_size_styles;

		if ( ( $original_size_scripts + $original_size_styles ) <= 0 ) {
			$percentage = 0;
		} else {
			$percentage = 100 - (int) $compressed_size * 100 / (int) $original_size;
		}
		$percentage = number_format_i18n( $percentage, 1 );

		$compressed_size_styles  = number_format( $original_size_styles - $compressed_size_styles, 0 );
		$compressed_size_scripts = number_format( $original_size_scripts - $compressed_size_scripts, 0 );

		// Internalization numbers.
		$original_size   = number_format_i18n( $original_size, 1 );
		$compressed_size = number_format_i18n( $compressed_size, 1 );

		$args = compact( 'enqueued_files', 'original_size', 'compressed_size', 'compressed_size_scripts', 'compressed_size_styles', 'percentage' );
		$this->view( 'dashboard/minification/module-meta-box', $args );
	}

	/**
	 * Asset optimization footer meta box.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_minification_module_metabox_footer() {
		$url = Utils::get_admin_menu_url( 'minification' );

		if ( is_multisite() && is_network_admin() ) {
			$cdn_status = false;
		} else {
			$cdn_status = Utils::get_module( 'minify' )->get_cdn_status();
		}

		$this->view( 'dashboard/minification/module-meta-box-footer', compact( 'url', 'cdn_status' ) );
	}

	/**
	 * Asset optimization network meta box.
	 */
	public function dashboard_minification_network_module_metabox() {
		$minify  = Utils::get_module( 'minify' );
		$options = $minify->get_options();

		$args = array(
			'enabled'          => $options['enabled'],
			'log'              => $options['log'],
			'use_cdn'          => $minify->get_cdn_status(),
			'use_cdn_disabled' => ! Utils::is_member() || ! $options['enabled'],
		);

		$this->view( 'dashboard/minification/network-module-meta-box', $args );
	}

	/**
	 * Asset optimization disabled meta box.
	 */
	public function dashboard_minification_disabled_metabox() {
		$minification_url = add_query_arg(
			array(
				'run'  => 'true',
				'type' => 'minification',
			),
			Utils::get_admin_menu_url()
		);
		$minification_url = wp_nonce_url( $minification_url, 'wphb-run-dashboard' ) . '#wphb-box-dashboard-minification-checking-files';
		$this->view( 'dashboard/minification/disabled-meta-box', compact( 'minification_url' ) );
	}

	/**
	 * *************************
	 * ADVANCED TOOLS
	 ***************************/

	/**
	 * Advanced tools meta box.
	 *
	 * @since 1.8
	 */
	public function dashboard_advanced_metabox() {
		$items = Advanced::get_db_count();
		$this->view(
			'dashboard/advanced/module-meta-box',
			array(
				'count' => $items->total,
			)
		);
	}

	/**
	 * Advanced tools meta box footer.
	 *
	 * @since 1.8
	 */
	public function dashboard_advanced_metabox_footer() {
		$this->view(
			'dashboard/advanced/module-meta-box-footer',
			array(
				'url' => Utils::get_admin_menu_url( 'advanced' ) . '&view=db',
			)
		);
	}

	/**
	 * *************************
	 * GZIP
	 ***************************/

	/**
	 * Dashboard gzip meta box.
	 */
	public function dashboard_gzip_module_metabox() {
		$this->view(
			'dashboard/gzip/module-meta-box',
			array(
				'status'         => $this->gzip_status,
				'inactive_types' => Utils::get_number_of_issues( 'gzip', $this->gzip_status ),
			)
		);
	}

	/**
	 * Dashboard gzip meta box header.
	 */
	public function dashboard_gzip_module_metabox_header() {
		$this->view(
			'dashboard/gzip/module-meta-box-header',
			array(
				'title'  => __( 'GZIP Compression', 'wphb' ),
				'issues' => Utils::get_number_of_issues( 'gzip', $this->gzip_status ),
			)
		);
	}

	/**
	 * Dashboard gzip meta box footer.
	 */
	public function dashboard_gzip_module_metabox_footer() {
		$this->view(
			'dashboard/gzip/module-meta-box-footer',
			array(
				'gzip_url' => Utils::get_admin_menu_url( 'gzip' ),
			)
		);
	}

	/**
	 * *************************
	 * PERFORMANCE
	 ***************************/

	/**
	 * Performance disabled meta box.
	 */
	public function dashboard_performance_disabled_metabox() {
		$run_url = add_query_arg(
			array(
				'run'  => 'true',
				'type' => 'performance',
			),
			Utils::get_admin_menu_url()
		);
		$run_url = wp_nonce_url( $run_url, 'wphb-run-dashboard' );

		$this->view( 'dashboard/performance/disabled-meta-box', compact( 'run_url' ) );
	}

	/**
	 * Performance meta box.
	 */
	public function dashboard_performance_module_metabox() {
		$this->view(
			'dashboard/performance/module-meta-box',
			array(
				'report'          => $this->performance->last_report->data->{$this->performance->type},
				'performance_url' => Utils::get_admin_menu_url( 'performance' ) . '&type=' . $this->performance->type,
			)
		);
	}

	/**
	 * Performance meta box dismissed.
	 */
	public function dashboard_performance_module_metabox_dismissed() {
		$notifications = false;
		if ( Utils::is_member() ) {
			$performance   = Utils::get_module( 'performance' );
			$options       = $performance->get_options();
			$notifications = $options['reports'];
		}

		$this->view( 'dashboard/performance/module-meta-box-dismissed', compact( 'notifications' ) );
	}

	/**
	 * Performance meta box header.
	 */
	public function dashboard_performance_module_metabox_header() {
		$last_report = $this->performance->last_report;
		if ( $last_report && ! is_wp_error( $last_report ) ) {
			$last_report = $last_report->data;
		}

		$scan_link = add_query_arg(
			array(
				'run'  => 'true',
				'type' => 'performance',
			),
			Utils::get_admin_menu_url()
		);

		$this->view(
			'dashboard/performance/module-meta-box-header',
			array(
				'title'            => __( 'Performance Test', 'wphb' ),
				'last_report'      => $last_report,
				'scan_link'        => wp_nonce_url( $scan_link, 'wphb-run-dashboard' ),
				'can_run_scan'     => Performance::can_run_test(),
				'report_dismissed' => $this->performance->report_dismissed,
			)
		);
	}

	/**
	 * Performance footer meta box.
	 *
	 * @since 1.7.0
	 */
	public function dashboard_performance_module_metabox_footer() {
		$url = Utils::get_admin_menu_url( 'performance' );

		$dismissed = $this->performance->report_dismissed;

		$this->view( 'dashboard/performance/module-meta-box-footer', compact( 'url', 'dismissed' ) );
	}

	/**
	 * Performance errors meta box.
	 */
	public function dashboard_performance_module_error_metabox() {
		$retry_url = add_query_arg(
			array(
				'run'  => 'true',
				'type' => 'performance',
			),
			Utils::get_admin_menu_url()
		);

		if ( is_wp_error( $this->performance->last_report ) ) {
			$error_msg = $this->performance->last_report->get_error_message();
		} else {
			$error_msg = sprintf(
				/* translators: %s - performance report type */
				esc_html__( 'There was a problem fetching the %s test results. Please try running a new scan.', 'wphb' ),
				esc_html( $this->performance->type )
			);
		}

		$this->view(
			'dashboard/performance/module-error-meta-box',
			array(
				'error'       => $error_msg,
				'retry_url'   => wp_nonce_url( $retry_url, 'wphb-run-dashboard' ),
				'support_url' => Utils::get_link( 'support' ),
			)
		);
	}

	/**
	 * *************************
	 * SMUSH
	 ***************************/

	/**
	 * Smush meta box.
	 */
	public function dashboard_smush_metabox() {
		$smush_data = array(
			'bytes'   => 0,
			'human'   => '',
			'percent' => 0,
		);

		$smush_enabled   = $this->is_smush_enabled();
		$smush_installed = $this->is_smush_installed();

		if ( $smush_enabled && $smush_installed ) {
			$smush_data = get_option( 'smush_global_stats', $smush_data );
		}

		$this->view(
			'dashboard/smush/meta-box',
			array(
				'activate_url'     => wp_nonce_url( 'plugins.php?action=activate&amp;plugin=wp-smushit/wp-smush.php', 'activate-plugin_wp-smushit/wp-smush.php' ),
				'activate_pro_url' => wp_nonce_url( 'plugins.php?action=activate&amp;plugin=wp-smush-pro/wp-smush.php', 'activate-plugin_wp-smush-pro/wp-smush.php' ),
				'can_activate'     => is_main_site() || is_network_admin(),
				'is_active'        => $smush_enabled,
				'is_installed'     => $smush_installed,
				'smush_data'       => $smush_data,
				'is_pro'           => $this->is_smush_pro,
			)
		);
	}

	/**
	 * Smush meta box haeder.
	 */
	public function dashboard_smush_metabox_header() {
		$title = __( 'Image Optimization', 'wphb' );
		$this->view( 'dashboard/smush/meta-box-header', compact( 'title' ) );
	}

	/**
	 * Smush meta box footer.
	 */
	public function dashboard_smush_metabox_footer() {
		$url = is_network_admin() ? network_admin_url( 'admin.php?page=smush' ) : admin_url( 'admin.php?page=smush' );
		$this->view( 'dashboard/smush/meta-box-footer', compact( 'url' ) );
	}

	/**
	 * *************************
	 * REPORTS
	 ***************************/

	/**
	 * Reports header meta box
	 *
	 * @since 1.4.5
	 */
	public function dashboard_reports_module_metabox_header() {
		$title = __( 'Reports', 'wphb' );
		$this->view( 'dashboard/reports/meta-box-header', compact( 'title' ) );
	}

}
