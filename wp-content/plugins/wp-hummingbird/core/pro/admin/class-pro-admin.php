<?php
/**
 * Admin class for Pro functions.
 *
 * @package Hummingbird\Core\Pro\Admin
 */

namespace Hummingbird\Core\Pro\Admin;

use Hummingbird\Admin\Notices;
use Hummingbird\Admin\Pages\Dashboard;
use Hummingbird\Admin\Pages\Notifications;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Pro_Admin
 */
class Pro_Admin {

	/**
	 * Admin notices.
	 *
	 * @var Notices
	 */
	public $admin_notices;

	/**
	 * Init function.
	 */
	public function init() {
		if ( ! Utils::is_member() ) {
			return;
		}

		$this->admin_notices = Notices::get_instance();

		// Dashboard is a little special. There's a bug that prevents to add meta boxes in another way.
		add_action( 'wphb_admin_do_meta_boxes_wphb', array( $this, 'register_dashboard_do_meta_boxes' ), 10 );

		// Notifications.
		add_action( 'wphb_admin_do_meta_boxes_wphb-notifications', array( $this, 'register_notifications_meta_boxes' ), 10 );
	}

	/**
	 * Register Dashboard Reporting meta box.
	 *
	 * @param Dashboard $dashboard_page  Dashboard page.
	 */
	public function register_dashboard_do_meta_boxes( $dashboard_page ) {
		if ( ! is_multisite() || is_network_admin() ) {
			/* Reports */
			$dashboard_page->add_meta_box(
				'dashboard-reports',
				__( 'Notifications', 'wphb' ),
				array( $this, 'dashboard_reports_meta_box' ),
				null,
				array( $this, 'dashboard_reports_meta_box_footer' ),
				'box-dashboard-right'
			);
		}
	}

	/**
	 * Register Notifications meta boxes.
	 *
	 * @since 3.1.1
	 *
	 * @param Notifications $notifications  Notifications page.
	 */
	public function register_notifications_meta_boxes( $notifications ) {
		$this->render_notification_module_notices();

		$notifications->add_meta_box(
			'notifications/summary',
			null,
			array( $this, 'summary_meta_box' ),
			null,
			null,
			'main',
			array(
				'box_class'         => 'sui-box sui-summary sui-summary-sm ' . Utils::get_whitelabel_class(),
				'box_content_class' => false,
			)
		);

		$notifications->add_meta_box(
			'pro/notifications/configure',
			__( 'Configure', 'wphb' ),
			array( $this, 'notifications_meta_box' ),
			null,
			null,
			'main',
			array(
				'box_content_class' => '',
			)
		);
	}

	/**
	 * Load an admin PRO view
	 *
	 * @param string $name  Meta box name.
	 * @param array  $args  Arguments array.
	 */
	public function pro_view( $name, $args = array() ) {
		$file    = WPHB_DIR_PATH . "core/pro/admin/views/$name.php";
		$content = '';

		if ( is_file( $file ) ) {
			ob_start();

			if ( isset( $args['id'] ) ) {
				$args['orig_id'] = $args['id'];
				$args['id']      = str_replace( '/', '-', $args['id'] );
			}

			extract( $args );
			include $file;

			$content = ob_get_clean();
		}

		echo $content;
	}

	/**
	 * Get modal file by type.
	 *
	 * @since 3.1.1
	 *
	 * @param string $type  Modal id.
	 * @param array  $args  Arguments array.
	 */
	public function pro_modal( $type, $args = array() ) {
		if ( empty( $type ) ) {
			return;
		}

		$content = '';

		$type = strtolower( $type );
		$file = WPHB_DIR_PATH . "core/pro/admin/modals/$type.php";

		if ( file_exists( $file ) ) {
			ob_start();
			extract( $args );
			include $file;
			$content = ob_get_clean();
		}

		echo $content;
	}

	/**
	 * *************************
	 * DASHBOARD
	 *
	 * @since 1.4.5
	 ***************************/

	/**
	 * Reports meta box
	 */
	public function dashboard_reports_meta_box() {
		$performance = Settings::get_setting( 'reports', 'performance' );

		$uptime = Settings::get_settings( 'uptime' );

		$database = Settings::get_setting( 'reports', 'database' );

		$this->pro_view(
			'dashboard/reports-meta-box',
			array(
				'database_reports'          => isset( $database['enabled'] ) ? $database['enabled'] : false,
				'database_reports_next'     => Utils::pro()->module( 'notifications' )->get_schedule_label_for( 'database', 'reports' ),
				'notifications_url'         => Utils::get_admin_menu_url( 'notifications' ),
				'performance_reports'       => isset( $performance['enabled'] ) ? $performance['enabled'] : false,
				'performance_reports_next'  => Utils::pro()->module( 'notifications' )->get_schedule_label_for( 'performance', 'reports' ),
				'uptime_enabled'            => isset( $uptime['enabled'] ) ? $uptime['enabled'] : false,
				'uptime_notifications'      => isset( $uptime['notifications']['enabled'] ) ? $uptime['notifications']['enabled'] : false,
				'uptime_reports'            => isset( $uptime['reports']['enabled'] ) ? $uptime['reports']['enabled'] : false,
				'uptime_notifications_next' => Utils::pro()->module( 'notifications' )->get_schedule_label_for( 'uptime', 'notifications' ),
				'uptime_reports_next'       => Utils::pro()->module( 'notifications' )->get_schedule_label_for( 'uptime', 'reports' ),
				'uptime_url'                => Utils::get_admin_menu_url( 'uptime' ),
			)
		);
	}

	/**
	 * Reports meta box footer.
	 *
	 * @since 3.1.1
	 */
	public function dashboard_reports_meta_box_footer() {
		$notifications_url = Utils::get_admin_menu_url( 'notifications' );
		$this->pro_view( 'dashboard/reports-meta-box-footer', compact( 'notifications_url' ) );
	}

	/**
	 * *************************
	 * NOTIFICATIONS
	 *
	 * @since 3.1.1
	 ***************************/

	/**
	 * Render notices for the notifications' module.
	 *
	 * @since 3.1.1
	 */
	private function render_notification_module_notices() {
		$status = filter_input( INPUT_GET, 'status', FILTER_UNSAFE_RAW );

		if ( ! $status ) {
			return;
		}

		$notice = '';
		if ( 'configured' === $status ) {
			$notice = __( 'Notification enabled successfully.', 'wphb' );
		} elseif ( 'disabled' === $status ) {
			$notice = __( 'Notification disabled successfully.', 'wphb' );
		} elseif ( 'updated' === $status ) {
			$notice = __( 'Settings have been updated successfully.', 'wphb' );
		}

		if ( ! empty( $notice ) ) {
			$this->admin_notices->show_floating( $notice );
		}
	}

	/**
	 * Summary meta box.
	 *
	 * @since 3.1.1
	 */
	public function summary_meta_box() {
		$notifications = Utils::pro()->module( 'notifications' );

		$active_notifications = $notifications->get_number_of_active_notifications();
		$next_notification    = $notifications->get_next_scheduled_report();

		$this->pro_view( 'notifications/summary-meta-box', compact( 'active_notifications', 'next_notification' ) );
	}

	/**
	 * Configure notifications meta box.
	 *
	 * @since 3.1.1
	 */
	public function notifications_meta_box() {
		$notifications = array(
			'reports'       => array(
				'performance' => array(
					'module_disabled' => false, // This is regards to the module, not the report.
					'recipients'      => array(),
					'desc'            => esc_attr__( 'Schedule performance tests and receive customized results by email.', 'wphb' ),
					'label'           => esc_attr__( 'Performance Test', 'wphb' ),
				),
				'uptime'      => array(
					'module_disabled' => false,
					'recipients'      => array(),
					'desc'            => esc_attr__( 'Schedule uptime reports and receive results by email.', 'wphb' ),
					'label'           => esc_attr__( 'Uptime', 'wphb' ),
				),
				'database'    => array(
					'module_disabled' => false,
					'recipients'      => array(),
					'desc'            => esc_attr__( 'Schedule database cleanups and receive results by email.', 'wphb' ),
					'label'           => esc_attr__( 'Database Cleanup', 'wphb' ),
				),
			),
			'notifications' => array(
				'uptime' => array(
					'module_disabled' => false,
					'recipients'      => array(),
					'desc'            => esc_attr__( 'Receive an email when this website is unavailable.', 'wphb' ),
					'label'           => esc_attr__( 'Uptime', 'wphb' ),
				),
			),
		);

		if ( is_multisite() && ! is_network_admin() ) {
			unset( $notifications['reports']['uptime'] );
			unset( $notifications['reports']['database'] );
			unset( $notifications['notifications']['uptime'] );
		}

		foreach ( $notifications as $type => $reports ) {
			foreach ( $reports as $module => $data ) {
				$settings = Settings::get_settings( $module );

				if ( ! isset( $settings[ $type ] ) ) {
					continue;
				}

				// The module is disabled, no reports are active.
				if ( isset( $settings['enabled'] ) && false === $settings['enabled'] ) {
					$notifications[ $type ][ $module ]['module_disabled'] = true;
					$notifications[ $type ][ $module ]['activate_url']    = Utils::get_admin_menu_url( $module );
					continue;
				}

				$schedule = array(
					'frequency' => isset( $settings[ $type ]['frequency'] ) ? $settings[ $type ]['frequency'] : 7,
					'time'      => isset( $settings[ $type ]['time'] ) ? $settings[ $type ]['time'] : false,
					'threshold' => isset( $settings[ $type ]['threshold'] ) ? $settings[ $type ]['threshold'] : 0,
				);

				if ( 7 === $schedule['frequency'] ) {
					$schedule['weekDay']  = isset( $settings[ $type ]['day'] ) ? $settings[ $type ]['day'] : false;
					$schedule['monthDay'] = false;
				} elseif ( 30 === $schedule['frequency'] ) {
					$schedule['weekDay']  = false;
					$schedule['monthDay'] = isset( $settings[ $type ]['day'] ) ? (int) $settings[ $type ]['day'] : false;
				}

				$data = array(
					'enabled'    => isset( $settings[ $type ]['enabled'] ) ? $settings[ $type ]['enabled'] : false,
					'recipients' => isset( $settings[ $type ]['recipients'] ) ? $settings[ $type ]['recipients'] : array(),
					'schedule'   => $schedule,
					'frequency'  => Utils::pro()->module( 'notifications' )->get_schedule_label_for( $module, $type ),
					'next'       => Utils::pro()->module( 'notifications' )->get_schedule_tooltip( $module, $type ),
				);

				// Remove the minutes from the hour to not confuse the user.
				if ( false !== $data['schedule']['time'] ) {
					$send_time    = explode( ':', $data['schedule']['time'] );
					$send_time[1] = '00';

					$data['schedule']['time'] = implode( ':', $send_time );
				}

				// Add settings to performance reports.
				if ( 'performance' === $module && 'reports' === $type ) {
					$data['settings'] = array(
						'device'    => isset( $settings[ $type ]['type'] ) ? $settings[ $type ]['type'] : 'both',
						'metrics'   => isset( $settings[ $type ]['metrics'] ) ? $settings[ $type ]['metrics'] : true,
						'audits'    => isset( $settings[ $type ]['audits'] ) ? $settings[ $type ]['audits'] : true,
						'fieldData' => isset( $settings[ $type ]['historic'] ) ? $settings[ $type ]['historic'] : true,
					);
				} elseif ( 'uptime' === $module && 'reports' === $type ) {
					$data['settings']['showPing'] = isset( $settings[ $type ]['show_ping'] ) ? $settings[ $type ]['show_ping'] : true;
				} elseif ( 'database' === $module && 'reports' === $type ) {
					$data['settings'] = array(
						'revisions'         => isset( $settings[ $type ]['tables']['revisions'] ) ? $settings[ $type ]['tables']['revisions'] : true,
						'drafts'            => isset( $settings[ $type ]['tables']['drafts'] ) ? $settings[ $type ]['tables']['drafts'] : true,
						'trash'             => isset( $settings[ $type ]['tables']['trash'] ) ? $settings[ $type ]['tables']['trash'] : true,
						'spam'              => isset( $settings[ $type ]['tables']['spam'] ) ? $settings[ $type ]['tables']['spam'] : true,
						'trashComment'      => isset( $settings[ $type ]['tables']['trash_comment'] ) ? $settings[ $type ]['tables']['trash_comment'] : true,
						'expiredTransients' => isset( $settings[ $type ]['tables']['expired_transients'] ) ? $settings[ $type ]['tables']['expired_transients'] : true,
						'transients'        => isset( $settings[ $type ]['tables']['transients'] ) ? $settings[ $type ]['tables']['transients'] : false,
					);
				}

				if ( ! empty( $data['recipients'] ) ) {
					$data['recipients'] = Utils::pro()->module( 'notifications' )->get_avatars( $data['recipients'] );
				}

				// This is only available for uptime reports.
				if ( 'uptime' === $module ) {
					$data['show_ping'] = isset( $options['show_ping'] ) ? $options['show_ping'] : true;
				}

				$notifications[ $type ][ $module ] = array_merge( $notifications[ $type ][ $module ], $data );
			}
		}

		$this->pro_view(
			'notifications/configure-meta-box',
			compact( 'notifications' )
		);

		$this->pro_modal( 'add-notification' );
	}

}