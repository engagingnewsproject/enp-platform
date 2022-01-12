<?php
/**
 * Notifications module. Handles all notifcations related functionality.
 *
 * @since 3.1.1
 * @package Hummingbird\Core\Pro\Modules
 */

namespace Hummingbird\Core\Pro\Modules;

use Hummingbird\Core\Module;
use Hummingbird\Core\Settings;
use Hummingbird\Core\Traits\Module as ModuleContract;
use WP_User_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Notifications
 *
 * @since 3.1.1
 */
class Notifications extends Module {

	use ModuleContract;

	/**
	 * Add an 'avatar' value to recipients array for each user.
	 *
	 * @since 3.1.1
	 *
	 * @param array $recipients  Recipients array.
	 *
	 * @return array
	 */
	public function get_avatars( $recipients ) {
		foreach ( $recipients as $id => $recipient ) {
			$recipients[ $id ]['avatar'] = get_avatar_url( $recipient['email'] );
		}

		return $recipients;
	}

	/**
	 * Query users.
	 *
	 * @since 3.1.1
	 *
	 * @param {string} $search_string  Search query.
	 * @param {array}  $exclude        Array of user IDs to exclude from search.
	 *
	 * @return array
	 */
	public function get_users( $search_string, $exclude ) {
		$params = array(
			'orderby'        => 'ID',
			'order'          => 'DESC',
			'number'         => 10,
			'paged'          => 1,
			'exclude'        => $exclude,
			'search'         => strtolower( $search_string ),
			'search_columns' => array(
				'user_login',
				'user_email',
				'user_nicename',
				'display_name',
			),
		);

		$user_query = new WP_User_Query( $params );

		$users = array();
		foreach ( $user_query->get_results() as $user ) {
			$users[] = array(
				'id'     => $user->ID,
				'name'   => $user->get( 'display_name' ),
				'email'  => $user->get( 'user_email' ),
				'role'   => empty( $user->roles ) ? null : ucfirst( $user->roles[0] ),
				'avatar' => get_avatar_url( $user->get( 'user_email' ) ),
			);
		}

		return $users;
	}

	/**
	 * Get a label from report schedule data.
	 *
	 * Instant | 5 minutes | 15 minutes | 30 minutes
	 * Daily, 3:00 AM
	 * Weekly On Sunday, 4:00 AM
	 * Monthly/1, 4:30 AM
	 *
	 * @since 3.1.1
	 *
	 * @param string $module  Accepts: performance, uptime.
	 * @param string $type    Accepts: notifications, reports.
	 *
	 * @return string
	 */
	public function get_schedule_label_for( $module, $type ) {
		$settings = Settings::get_setting( $type, $module );

		if ( ! $settings ) {
			return '';
		}

		// Uptime notifications.
		if ( 'notifications' === $type && 'uptime' === $module ) {
			if ( ! isset( $settings['threshold'] ) ) {
				return '';
			}

			if ( 0 === $settings['threshold'] ) {
				return __( 'Instant', 'wphb' );
			}

			return sprintf( /* translators: %d - number of minutes */
				esc_html__( '%d minutes', 'wphb' ),
				(int) $settings['threshold']
			);
		}

		if ( ! isset( $settings['frequency'] ) || ! isset( $settings['time'] ) ) {
			return '';
		}

		// Reports.
		if ( 1 === $settings['frequency'] ) {
			$label = sprintf( /* translators: %s - time */
				esc_html__( 'Daily, %s', 'wphb' ),
				date_format( date_create( $settings['time'] ), 'h:00 A' )
			);
		}

		// Weekly.
		if ( 7 === $settings['frequency'] && isset( $settings['day'] ) ) {
			$label = sprintf( /* translators: %s - week day, time */
				esc_html__( 'Weekly on %s', 'wphb' ),
				date_format( date_create( $settings['day'] . ', ' . $settings['time'] ), 'l, h:00 A' )
			);
		}

		// Monthly.
		if ( 30 === $settings['frequency'] && isset( $settings['day'] ) ) {
			$label = sprintf( /* translators: %1$s - day of month, %2$s - time */
				esc_html__( 'Monthly/%1$s, %2$s', 'wphb' ),
				(int) $settings['day'],
				date_format( date_create( $settings['time'] ), 'h:00 A' )
			);
		}

		return isset( $label ) ? $label : '';
	}

	/**
	 * Get a tooltip text from report schedule data.
	 *
	 * Next scheduled report: MM DD, YYYY HH:MM AM|PM
	 *
	 * @since 3.1.1
	 *
	 * @param string $module      Accepts: performance, uptime.
	 * @param string $type        Accepts: notifications, reports.
	 * @param bool   $show_label  Show the "Next scheduled report" label for reports.
	 *
	 * @return string
	 */
	public function get_schedule_tooltip( $module, $type, $show_label = true ) {
		$settings = Settings::get_setting( $type, $module );

		if ( isset( $settings['threshold'] ) ) {
			if ( 0 === $settings['threshold'] ) {
				return __( 'Notified instantly', 'wphb' );
			}

			return sprintf( /* translators: %d - number of minutes */
				esc_html__( 'Notified after %d minutes of inactivity', 'wphb' ),
				(int) $settings['threshold']
			);
		}

		$time_str = $this->get_reports_time_string( $settings );

		if ( $show_label ) {
			return sprintf( /* translators: %s - week day, time */
				esc_html__( 'Next scheduled report: %s', 'wphb' ),
				date_format( date_create( $time_str ), 'F d, Y h:00 A' )
			);
		}

		return date_format( date_create( $time_str ), 'F d, Y h:00 A' );
	}


	/**
	 * Get time string in a format accepted by strtotime().
	 *
	 * @since 3.1.1
	 *
	 * @param array $settings  Report settings.
	 *
	 * @return string
	 */
	public function get_reports_time_string( $settings ) {
		if ( ! is_array( $settings ) || ! isset( $settings['frequency'] ) ) {
			return '';
		}

		if ( 1 === $settings['frequency'] ) {
			$time_str = wp_date( 'd-m-Y' ) . ' ' . $settings['time'];
			if ( date_create( date_i18n( 'H:i' ) ) > date_create( $settings['time'] ) ) {
				$time_str = 'tomorrow ' . $time_str;
			}
		} elseif ( 7 === $settings['frequency'] ) {
			if ( empty( $settings['day'] ) ) {
				return '';
			}

			$time_str = $settings['day'] . ', ' . $settings['time'];
			if ( date_create( date_i18n( 'H:i' ) ) > date_create( $settings['time'] ) ) {
				$time_str = 'next ' . $settings['day'] . ', ' . $settings['time'];
			}
		} elseif ( 30 === $settings['frequency'] ) {
			$current_day = wp_date( 'd' );
			$month       = wp_date( 'm' );
			$year        = wp_date( 'Y' );

			if ( (int) $current_day > (int) $settings['day'] ) {
				if ( 12 === (int) $month ) {
					$month = 1;
					(int) $year++;
				} else {
					(int) $month++;
				}
			}

			$time_str = $settings['day'] . '-' . $month . '-' . $year . ', ' . $settings['time'];
			if ( (int) $current_day === (int) $settings['day'] && date_create( date_i18n( 'H:i' ) ) > date_create( $settings['time'] ) ) {
				$time_str = 'next month ' . $time_str;
			}
		}

		if ( ! isset( $time_str ) ) {
			return '';
		}

		return $time_str;
	}

	/**
	 * Get number of active notifications.
	 *
	 * @since 3.1.1
	 *
	 * @return int
	 */
	public function get_number_of_active_notifications() {
		$notifications = array(
			'reports'       => array( 'performance', 'uptime', 'database' ),
			'notifications' => array( 'uptime' ),
		);

		$active = 0;
		foreach ( $notifications as $type => $reports ) {
			foreach ( $reports as $module ) {
				$settings = Settings::get_setting( $type, $module );

				if ( ! $settings ) {
					continue;
				}

				if ( ! isset( $settings['enabled'] ) || ! $settings['enabled'] ) {
					continue;
				}

				$active++;
			}
		}

		return $active;
	}

	/**
	 * Get the next scheduled notification.
	 *
	 * @since 3.1.1.
	 *
	 * @return string
	 */
	public function get_next_scheduled_report() {
		$modules = array(
			'performance' => wp_next_scheduled( 'wphb_performance_report' ),
			'uptime'      => wp_next_scheduled( 'wphb_uptime_report' ),
			'database'    => wp_next_scheduled( 'wphb_database_report' ),
		);

		foreach ( $modules as $module => $value ) {
			if ( false === $value ) {
				unset( $modules[ $module ] );
			}
		}

		if ( empty( $modules ) ) {
			return esc_html__( 'Never', 'wphb' );
		}

		$module = array_search( min( $modules ), $modules, true );

		$next = $this->get_schedule_tooltip( $module, 'reports', false );

		if ( '' === $next ) {
			return esc_html__( 'Never', 'wphb' );
		}

		return $next;
	}

}