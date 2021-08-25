<?php
/**
 * Performance page.
 *
 * @package Hummingbird\Admin\Pages
 */

namespace Hummingbird\Admin\Pages;

use Hummingbird\Admin\Page;
use Hummingbird\Core\Modules\Performance as Performance_Report;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Performance
 */
class Performance extends Page {

	use \Hummingbird\Core\Traits\Smush;

	/**
	 * Status of error. If true, than we have some error.
	 *
	 * @since 1.8.2  Changed to private.
	 *
	 * @var bool $has_error True if error present.
	 */
	private $has_error = false;

	/**
	 * Latest report.
	 *
	 * @since 1.8.2
	 *
	 * @var WP_Error|array|object $report  Latest performance report.
	 */
	private $report;

	/**
	 * Report dismissed.
	 *
	 * @since 1.8.2
	 *
	 * @var bool $dismissed  Dismiss status.
	 */
	private $dismissed = false;

	/**
	 * Can run new performance test.
	 *
	 * @since 1.8.2
	 *
	 * @var bool $can_run_test
	 */
	private $can_run_test = true;

	/**
	 * Report type: desktop or mobile.
	 *
	 * @since 2.0.0
	 *
	 * @var string $type
	 */
	private $type = 'desktop';

	/**
	 * Render header.
	 */
	public function render_header() {
		if ( filter_input( INPUT_GET, 'report-dismissed' ) ) {
			$this->admin_notices->show_floating( __( 'You have successfully ignored this performance test.', 'wphb' ) );
		}

		add_filter( 'wphb_admin_after_flat_tab_' . $this->get_slug(), array( $this, 'after_flat_tab' ) );

		parent::render_header();
	}

	/**
	 * Overwrite parent render_inner_content method.
	 *
	 * Render content for display.
	 *
	 * @since 1.8.2
	 */
	protected function render_inner_content() {
		$this->view(
			$this->slug . '-page',
			array(
				'report' => $this->report,
			)
		);
	}

	/**
	 * Add the test button.
	 *
	 * @since 3.0.0
	 */
	public function after_flat_tab() {
		if ( true === $this->can_run_test ) {
			$run_url = add_query_arg( 'run', 'true', $this->get_page_url() );
			$run_url = wp_nonce_url( $run_url, 'wphb-run-performance-test' );
			?>
			<div class="sui-actions-right">
				<a href="<?php echo esc_url( $run_url ); ?>" class="sui-button sui-button-blue" id="performance-run-test">
					<?php esc_html_e( 'New Test', 'wphb' ); ?>
				</a>
			</div>
			<?php
		} else {
			$tooltip = sprintf(
				/* translators: %d: number of minutes. */
				_n(
					'Hummingbird is just catching her breath - you can run another test in %d minute',
					'Hummingbird is just catching her breath - you can run another test in %d minutes',
					$this->can_run_test,
					'wphb'
				),
				number_format_i18n( $this->can_run_test )
			);
			?>
			<div class="sui-actions-right">
				<span class="sui-tooltip sui-tooltip-bottom sui-tooltip-constrained sui-tooltip-bottom-right" disabled="disabled" data-tooltip="<?php echo esc_attr( $tooltip ); ?>" aria-hidden="true">
					<a href="#" class="sui-button" disabled="disabled" aria-hidden="true">
						<?php esc_html_e( 'New Test', 'wphb' ); ?>
					</a>
				</span>
			</div>
			<?php
		}
	}

	/**
	 * Function triggered when the page is loaded before render any content.
	 */
	public function on_load() {
		$this->tabs = array(
			'main'     => __( 'Performance Report', 'wphb' ),
			'reports'  => __( 'Reporting', 'wphb' ),
			'settings' => __( 'Settings', 'wphb' ),
		);

		if ( is_multisite() && ! is_network_admin() ) {
			unset( $this->tabs['reports'] );
		}

		if ( isset( $_GET['run'] ) ) { // Input var ok.
			check_admin_referer( 'wphb-run-performance-test' );

			if ( Performance_Report::is_doing_report() ) {
				return;
			}

			// Start the test.
			Utils::get_module( 'performance' )->init_scan();

			wp_safe_redirect( remove_query_arg( array( 'run', '_wpnonce' ) ) );
			exit;
		}

		// Process form submit from expiry settings.
		if ( isset( $_POST['dismiss_report'] ) ) { // Input var ok.
			check_admin_referer( 'wphb-dismiss-performance-report' );

			Performance_Report::dismiss_report( true );

			$redirect_to = add_query_arg(
				array(
					'report-dismissed' => true,
				)
			);
			wp_safe_redirect( $redirect_to );
		}
	}

	/**
	 * Init performance module, prior to page load.
	 *
	 * The logic behind this is following:
	 * - First check if there's a report in the db.
	 * - If not - check one on the API.
	 * - If no report on API, display the error that no report was found.
	 *
	 * @since 2.0.0
	 */
	private function init() {
		$selected_type = filter_input( INPUT_GET, 'type', FILTER_SANITIZE_STRING );
		if ( $selected_type ) {
			$this->type = $selected_type;
		}

		$is_doing_report = Utils::get_module( 'performance' )->is_doing_report();

		// Try to get the current report from the database.
		if ( ! $is_doing_report ) {
			// This needs to be here, because it's the first block that runs on page load.
			$this->report = Performance_Report::get_last_report();
		}

		// Is that a report with errors?
		if ( is_wp_error( $this->report ) || ( $this->report && is_null( $this->report->data->{$this->type}->metrics ) ) ) {
			$this->has_error = true;
		}

		$this->dismissed    = Performance_Report::report_dismissed( $this->report );
		$this->can_run_test = Performance_Report::can_run_test( $this->report );
	}

	/**
	 * Register meta boxes.
	 */
	public function register_meta_boxes() {
		$this->init();

		// Default to empty meta box if doing performance scan, or we will get php notices.
		if ( Utils::get_module( 'performance' )->is_doing_report() || ! $this->report ) {
			/**
			 * Empty meta box.
			 */
			$this->add_meta_box(
				'performance/empty',
				__( 'Performance Report', 'wphb' ),
				null,
				null,
				null,
				'main',
				array(
					'box_content_class' => 'sui-box sui-message',
				)
			);

			return;
		}

		if ( $this->has_error ) {
			/**
			 * Error meta box.
			 */
			$this->add_meta_box(
				'performance/error',
				__( 'Performance Report', 'wphb' ),
				array( $this, 'error_metabox' )
			);
		}

		/**
		 * Summary meta box.
		 */
		$this->add_meta_box(
			'performance-welcome',
			null,
			array( $this, 'summary_metabox' ),
			null,
			null,
			'summary',
			array(
				'box_class'         => 'sui-box sui-summary ' . Utils::get_whitelabel_class(),
				'box_content_class' => false,
			)
		);

		if ( $this->report && ! $this->has_error ) {
			/**
			 * Score Metrics meta box.
			 */
			$this->add_meta_box(
				'performance/metrics',
				__( 'Performance Report', 'wphb' ),
				array( $this, 'metrics_metabox' )
			);

			/**
			 * Audits meta boxes.
			 */
			$this->add_meta_box(
				'performance/audits',
				__( 'Audits', 'wphb' ),
				array( $this, 'audits_meta_box' )
			);

			if ( is_multisite() && is_network_admin() || ! is_multisite() ) {
				$this->add_meta_box(
					'performance/reporting',
					__( 'Reporting', 'wphb' ),
					null,
					null,
					null,
					'reports',
					array(
						'box_content_class' => 'sui-box-body sui-upsell-items',
					)
				);
			}
		}

		$this->add_meta_box(
			'settings-summary',
			__( 'Settings', 'wphb' ),
			array( $this, 'settings_metabox' ),
			null,
			array( $this, 'settings_metabox_footer' ),
			'settings'
		);
	}

	/**
	 * Performance metrics meta box.
	 */
	public function metrics_metabox() {
		$field_data = $this->report->data->{$this->type}->field_data;

		$fcp_fast = $fcp_average = $fcp_slow = false;
		$fid_fast = $fid_average = $fid_slow = false;

		if ( $field_data ) {
			$fcp_fast    = round( $field_data->FIRST_CONTENTFUL_PAINT_MS->distributions[0]->proportion * 100 );
			$fcp_average = round( $field_data->FIRST_CONTENTFUL_PAINT_MS->distributions[1]->proportion * 100 );
			$fcp_slow    = round( $field_data->FIRST_CONTENTFUL_PAINT_MS->distributions[2]->proportion * 100 );

			$fid_fast    = round( $field_data->FIRST_INPUT_DELAY_MS->distributions[0]->proportion * 100 );
			$fid_average = round( $field_data->FIRST_INPUT_DELAY_MS->distributions[1]->proportion * 100 );
			$fid_slow    = round( $field_data->FIRST_INPUT_DELAY_MS->distributions[2]->proportion * 100 );

			$i10n = array(
				'fcp' => array(
					'fast'         => $fcp_fast,
					'fast_desc'    => sprintf(
					/* translators: %d - number of percent */
						esc_html__( '%d%% of loads for this page have a fast (< 1 s) First Contentful Paint (FCP).', 'wphb' ),
						absint( $fcp_fast )
					),
					'average'      => $fcp_average,
					'average_desc' => sprintf(
					/* translators: %d - number of percent */
						esc_html__( '%d%% of loads for this page have an average (1 s ~ 2.5 s) First Contentful Paint (FCP).', 'wphb' ),
						absint( $fcp_average )
					),
					'slow'         => $fcp_slow,
					'slow_desc'    => sprintf(
					/* translators: %d - number of percent */
						esc_html__( '%d%% of loads for this page have a slow (> 2.5 s) First Contentful Paint (FCP).', 'wphb' ),
						absint( $fcp_slow )
					),
				),
				'fid' => array(
					'fast'         => $fid_fast,
					'fast_desc'    => sprintf(
					/* translators: %d - number of percent */
						esc_html__( '%d%% of loads for this page have a fast (< 50 ms) First Input Delay (FID).', 'wphb' ),
						absint( $fid_fast )
					),
					'average'      => $fid_average,
					'average_desc' => sprintf(
					/* translators: %d - number of percent */
						esc_html__( '%d%% of loads for this page have an average (50 ms ~ 250 ms) First Input Delay (FID).', 'wphb' ),
						absint( $fid_average )
					),
					'slow'         => $fid_slow,
					'slow_desc'    => sprintf(
					/* translators: %d - number of percent */
						esc_html__( '%d%% of loads for this page have a slow (> 250 ms) First Input Delay (FID).', 'wphb' ),
						absint( $fid_slow )
					),
				),
			);

			wp_localize_script( 'wphb-google-chart', 'wphbHistoricFieldData', $i10n );
		}

		$this->view(
			'performance/metrics-meta-box',
			array(
				'can_run_test'     => $this->can_run_test,
				'field_data'       => $field_data,
				'historic_data'    => array(
					'fcp_fast'    => $fcp_fast,
					'fcp_average' => $fcp_average,
					'fcp_slow'    => $fcp_slow,
					'fid_fast'    => $fid_fast,
					'fid_average' => $fid_average,
					'fid_slow'    => $fid_slow,
				),
				'last_test'        => $this->report->data->{$this->type},
				'links'            => array(
					'speed-index'              => 'https://web.dev/speed-index/',
					'first-contentful-paint'   => 'https://web.dev/first-contentful-paint/',
					'largest-contentful-paint' => 'https://web.dev/lighthouse-largest-contentful-paint/',
					'interactive'              => 'https://web.dev/interactive/',
					'total-blocking-time'      => 'https://web.dev/lighthouse-total-blocking-time/',
					'cumulative-layout-shift'  => 'https://web.dev/cls/',
				),
				'report_dismissed' => $this->dismissed,
				'retry_url'        => wp_nonce_url(
					add_query_arg( 'run', 'true', $this->get_page_url() ),
					'wphb-run-performance-test'
				),
				'tooltips'         => array(
					'speed-index'              => sprintf( /* translators: %s - number of seconds */
						esc_html__( 'Speed Index (SI) shows how quickly the contents of your page are visibly populated. A good score is %ss or less.', 'wphb' ),
						'desktop' === $this->type ? 1.3 : 3.3
					),
					'first-contentful-paint'   => sprintf( /* translators: %s - number of seconds */
						esc_html__( 'First Contentful Paint (LCP) marks the time at which the first text or image is rendered on your page. A good score is %ss or less.', 'wphb' ),
						'desktop' === $this->type ? 0.9 : 1.8
					),
					'largest-contentful-paint' => sprintf( /* translators: %s - number of seconds */
						esc_html__( 'Largest Contentful Paint (LCP) marks the time at which the largest text or image is rendered on your page. A good score is %ss or less.', 'wphb' ),
						'desktop' === $this->type ? 1.2 : 2.5
					),
					'interactive'              => sprintf( /* translators: %s - number of seconds */
						esc_html__( 'Time to Interactive (TTI) is the amount of time it takes for your page to become fully interactive. A good score is %ss or less.', 'wphb' ),
						'desktop' === $this->type ? 2.4 : 3.7
					),
					'total-blocking-time'      => sprintf( /* translators: %d - number of milliseconds */
						esc_html__( 'Total Blocking Time (TBT)  measures the total amount of time, between FCP and TTI, that a page is blocked from responding to user input. A good score is %dms or less.', 'wphb' ),
						'desktop' === $this->type ? 150 : 200
					),
					'cumulative-layout-shift'  => __( "Cumulative Layout Shift (CLS) measures how much your page's layout shifts as it loads. A good score is 0.1 or less.", 'wphb' ),
				),
				'type'             => $this->type,
			)
		);
	}

	/**
	 * Performance welcome meta box.
	 */
	public function summary_metabox() {
		$last_report = $this->report;

		$opportunities = '-';
		$diagnostics   = '-';
		$passed_audits = '-';

		if ( $last_report && ! is_wp_error( $last_report ) ) {
			$last_report = $last_report->data;

			if ( ! is_null( $last_report->{$this->type}->audits->opportunities ) ) {
				$opportunities = count( get_object_vars( $last_report->{$this->type}->audits->opportunities ) );
			}

			if ( ! is_null( $last_report->{$this->type}->audits->diagnostics ) ) {
				$diagnostics = count( get_object_vars( $last_report->{$this->type}->audits->diagnostics ) );
			}

			if ( ! is_null( $last_report->{$this->type}->audits->passed ) ) {
				$passed_audits = count( get_object_vars( $last_report->{$this->type}->audits->passed ) );
			}
		}

		$this->view(
			'performance/summary-meta-box',
			array(
				'type'             => $this->type,
				'last_report'      => $last_report,
				'opportunities'    => $opportunities,
				'diagnostics'      => $diagnostics,
				'passed_audits'    => $passed_audits,
				'report_dismissed' => $this->dismissed,
				'is_doing_report'  => Performance_Report::is_doing_report(),
			)
		);
	}

	/**
	 * Settings meta box.
	 *
	 * @since 1.7.1
	 */
	public function settings_metabox() {
		$this->view(
			'performance/settings-meta-box',
			array(
				'dismissed'     => $this->dismissed,
				'subsite_tests' => Settings::get_setting( 'subsite_tests', 'performance' ),
			)
		);
	}

	/**
	 * Reporting meta box footer.
	 *
	 * @since 1.7.1
	 */
	public function settings_metabox_footer() {
		$this->view( 'performance/settings-meta-box-footer', array() );
	}

	/**
	 * Error meta box.
	 *
	 * @since 2.0.0
	 */
	public function error_metabox() {
		$error_text = sprintf(
			/* translators: %s - type of report */
			esc_html__( 'There was a problem fetching the %s test results. Please try running a new scan.', 'wphb' ),
			esc_html( $this->type )
		);

		$error_details = '';

		if ( is_wp_error( $this->report ) ) {
			$error_text    = $this->report->get_error_message();
			$error_details = $this->report->get_error_data();
		}

		if ( is_array( $error_details ) && isset( $error_details['details'] ) ) {
			$error_details = $error_details['details'];
		} else {
			$error_details = '';
		}

		$retry_url = wp_nonce_url(
			add_query_arg( 'run', 'true', $this->get_page_url() ),
			'wphb-run-performance-test'
		);

		$this->view(
			'performance/error-meta-box',
			array(
				'error_details' => $error_details,
				'error_text'    => $error_text,
				'retry_url'     => $retry_url,
			)
		);
	}

	/**
	 * Audit meta box.
	 *
	 * @since 3.1.0  Unified meta box for opportunities, diagnostics and passed audits.
	 */
	public function audits_meta_box() {
		$this->view(
			'performance/audits-meta-box',
			array(
				'audits'       => $this->report->data->{$this->type}->audits,
				'is_dismissed' => $this->dismissed,
				'maps'         => \Hummingbird\Core\Modules\Performance::get_maps(),
				'passed'       => false, // Default audit status.
			)
		);
	}

}
