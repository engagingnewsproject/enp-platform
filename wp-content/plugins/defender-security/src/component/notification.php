<?php

namespace WP_Defender\Component;

use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component;
use WP_Defender\Model\Notification as Abstract_Notification;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Notification\Firewall_Notification;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Model\Setting\Audit_Logging;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\User;

class Notification extends Component {
	use User, IO;

	/**
	 * @var bool
	 */
	private $is_pro;

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->is_pro = ( new WPMUDEV() )->is_pro();
	}

	/**
	 * @param array  $exclude
	 * @param string $role
	 * @param string $username
	 * @param string $order_by
	 * @param string $order
	 * @param int    $limit
	 *
	 * @return array
	 */
	public function get_users_pool(
		$exclude = array(),
		$role = '',
		$username = '',
		$order_by = 'ID',
		$order = 'ASC',
		$limit = 15,
		$paged = 1
	) {
		$params = array(
			'site_id' => 0,
			'role'    => $role,
			'orderby' => $order_by,
			'order'   => $order,
			'number'  => $limit,
			'paged'   => $paged,
			'exclude' => $exclude,
		);

		if ( ! empty( $username ) ) {
			$params['search']         = strtolower( $username );
			$params['search_columns'] = array(
				'user_login',
				'user_email',
				'user_nicename',
				'display_name',
			);
		}
		$user_query = new \WP_User_Query( $params );

		$pools = array();
		foreach ( $user_query->get_results() as $user ) {
			$pools[] = array(
				'name'   => $this->get_user_display( $user ),
				'email'  => $this->get_current_user_email( $user ),
				'role'   => $this->get_current_user_role( $user ),
				'avatar' => get_avatar_url( $this->get_current_user_email( $user ) ),
				'id'     => $user->ID,
				'status' => Abstract_Notification::USER_SUBSCRIBE_NA,
			);
		}

		return $pools;
	}

	/**
	 * Dispatch Firewall and Scan notifications.
	 *
	 * @param string $slug
	 * @param object $args
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function dispatch_notification( $slug, $args ) {
		$module = $this->find_module_by_slug( $slug );
		if ( ! is_object( $module ) ) {
			return;
		}

		if ( 'malware-notification' === $module->slug && $module->check_options() ) {
			// Case report.
			$module->send( $args );
		} elseif ( 'firewall-notification' === $module->slug && $module->check_options( $args ) ) {
			$module->send( $args );
		}
	}

	/**
	 * @param string $slug
	 *
	 * @return mixed
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function find_module_by_slug( $slug ) {
		switch ( $slug ) {
			case 'tweak-reminder':
				return wd_di()->get( Tweak_Reminder::class );
			case 'malware-notification':
				return wd_di()->get( Malware_Notification::class );
			case 'firewall-notification':
				return wd_di()->get( Firewall_Notification::class );
			case 'malware-report':
				return wd_di()->get( Malware_Report::class );
			case 'firewall-report':
				return wd_di()->get( Firewall_Report::class );
			case 'audit-report':
			default:
				return wd_di()->get( Audit_Report::class );
		}
	}

	/**
	 * Send a verification email to users.
	 *
	 * @param Abstract_Notification $model
	 */
	public function send_subscription_confirm_email( Abstract_Notification $model ) {
		foreach ( $model->in_house_recipients as &$subscriber ) {
			if ( empty( $subscriber['status'] ) ) {
				continue;
			}
			if ( Abstract_Notification::USER_SUBSCRIBE_NA !== $subscriber['status'] ) {
				continue;
			}
			$ret = $this->send_email( $subscriber, $model );

			if ( $ret ) {
				$subscriber['status'] = Abstract_Notification::USER_SUBSCRIBE_WAITING;
			}
		}
		foreach ( $model->out_house_recipients as &$subscriber ) {
			if ( empty( $subscriber['status'] ) ) {
				continue;
			}
			if ( Abstract_Notification::USER_SUBSCRIBE_NA !== $subscriber['status'] ) {
				continue;
			}
			$ret = $this->send_email( $subscriber, $model );

			if ( $ret ) {
				$subscriber['status'] = Abstract_Notification::USER_SUBSCRIBE_WAITING;
			}
		}

		$model->save();
	}

	/**
	 * @param array                 $subscriber
	 * @param Abstract_Notification $model
	 *
	 * @return bool
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function send_email( $subscriber, Abstract_Notification $model ) {
		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_confirm_noreply_email' )
		);
		$email   = $subscriber['email'];
		$name    = isset( $subscriber['name'] ) ? $subscriber['name'] : '';
		$inhouse = false;
		if ( isset( $subscriber['id'] ) ) {
			$inhouse = true;
		}
		$url     = $this->create_subscribe_url( $model->slug, $email, $inhouse );
		$subject = sprintf( 'Subscribe to %s', $model->title );
		// Renders emails.
		$notification = wd_di()->get( \WP_Defender\Controller\Notification::class );
		$content_body = $notification->render_partial(
			'email/confirm',
			array(
				'subject'           => $subject,
				'email'             => $email,
				'notification_name' => $model->title,
				'url'               => $url,
				'site_url'          => network_site_url(),
				'name'              => $name,
			),
			false
		);
		$content      = $notification->render_partial(
			'email/index',
			array(
				'title'             => preg_replace('/ - Notification$/', '', $model->title ),
				'content_body'      => $content_body,
			),
			false
		);

		// We send email here.
		return wp_mail( $email, $subject, $content, $headers );
	}

	/**
	 * @param string $email
	 * @param object $m
	 * @param string $name
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function send_subscribed_email( $email, $m, $name ) {
		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_subscribe_noreply_email' )
		);

		$notification = wd_di()->get( \WP_Defender\Controller\Notification::class );
		$subject      = __( 'Confirmed', 'wpdef' );
		$content_body = $notification->render_partial(
			'email/subscribed',
			array(
				'subject'           => __( 'Subscription Confirmed', 'wpdef' ),
				'notification_name' => $m->title,
				'url'               => $this->create_unsubscribe_url( $m->slug, $email ),
				'name'              => $name,
			)
		);
		$content      = $notification->render_partial(
			'email/index',
			array(
				'title'        => preg_replace( '/ - Notification$/', '', $m->title ),
				'content_body' => $content_body,
			),
			false
		);

		wp_mail( $email, $subject, $content, $headers );
	}

	/**
	 * Send unsubscribe email.
	 *
	 * @param object $m
	 * @param string $email
	 * @param bool   $inhouse
	 * @param string $name
	 */
	public function send_unsubscribe_email( $m, $email, $inhouse, $name ) {
		$subject  = __( 'Unsubscribed', 'wpdef' );
		$url      = $this->create_subscribe_url( $m->slug, $email, $inhouse );
		// Render emails.
		$notification = wd_di()->get( \WP_Defender\Controller\Notification::class );
		$content_body = $notification->render_partial(
			'email/unsubscribe',
			array(
				'subject'           => __( 'Unsubscribed', 'wpdef' ),
				'notification_name' => $m->title,
				'url'               => $url,
				'name'              => $name,
			)
		);
		$title        = preg_replace( '/ - Notification$/', '', $m->title );
		$title        = preg_replace( '/ - Reporting$/', '', $title );
		$content      = $notification->render_partial(
			'email/index',
			array(
				'title'        => $title,
				'content_body' => $content_body,
			),
			false
		);

		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_unsubscribe_noreply_email' )
		);

		wp_mail( $email, $subject, $content, $headers );
	}

	/**
	 * @param string $slug
	 * @param string $email
	 *
	 * @return string
	 */
	public function create_unsubscribe_url( $slug, $email ) {
		return add_query_arg(
			array(
				'action'  => 'defender_listen_user_unsubscribe',
				'hash'    => hash( 'sha256', $email . AUTH_SALT ),
				'slug'    => $slug,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * @param string $slug
	 * @param string $email
	 * @param bool   $inhouse User is In-house or not.
	 *
	 * @return string
	 */
	public function create_subscribe_url( $slug, $email, $inhouse ) {
		return add_query_arg(
			array(
				'action'  => 'defender_listen_user_subscribe',
				'hash'    => hash( 'sha256', $email . AUTH_SALT ),
				'uid'     => $slug,
				'inhouse' => $inhouse,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Get all modules as array of arrays.
	 *
	 * @return array
	 */
	public function get_modules() {
		$modules = array(
			wd_di()->get( Tweak_Reminder::class )->export(),
			wd_di()->get( Malware_Notification::class )->export(),
			wd_di()->get( Firewall_Notification::class )->export(),
		);

		if ( true === $this->is_pro ) {
			return array_merge( $modules, $this->get_active_pro_reports() );
		} else {
			return $modules;
		}
	}

	/**
	 * Get all modules as array of objects.
	 *
	 * @return array
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function get_modules_as_objects() {
		$modules = array(
			wd_di()->get( Tweak_Reminder::class ),
			wd_di()->get( Malware_Notification::class ),
			wd_di()->get( Firewall_Notification::class ),
		);

		if ( true === $this->is_pro ) {
			$modules   = array_merge(
				$modules,
				array(
					wd_di()->get( Malware_Report::class ),
					wd_di()->get( Firewall_Report::class ),
					wd_di()->get( Audit_Report::class ),
				)
			);
		}

		return $modules;
	}

	/**
	 * Return the time that next report will be trigger.
	 *
	 * @return mixed
	 */
	public function get_next_run() {
		if ( false === $this->is_pro ) {
			return __( 'Never', 'wpdef' );
		}
		$modules  = $this->get_active_pro_reports_as_objects();
		$next_run = null;
		foreach ( $modules as $module ) {
			if ( Abstract_Notification::STATUS_ACTIVE !== $module->status ) {
				continue;
			}
			if ( is_null( $next_run ) ) {
				$next_run = $module;
			} elseif ( $module->est_timestamp < $next_run->est_timestamp ) {
				$next_run = $module;
			}
		}
		if ( is_null( $next_run ) ) {
			return __( 'Never', 'wpdef' );
		}

		return $next_run->get_next_run_as_string();
	}

	/**
	 * Get inactive modules.
	 *
	 * @return array
	 * @since 2.7.0 Malware Scanning - Reporting may be inactive.
	 */
	public function get_inactive_modules() {
		if ( false === $this->is_pro ) {
			return array();
		}
		$modules = array();
		if ( false === wd_di()->get( \WP_Defender\Model\Setting\Scan::class )->scheduled_scanning ) {
			$module         = wd_di()->get( Malware_Report::class )->export();
			$module['link'] = network_admin_url( 'admin.php?page=wdf-scan&view=settings&enable=scheduled_scanning#setting_scheduled_scanning' );
			$modules[]      = $module;
		}
		if ( false === wd_di()->get( Audit_Logging::class )->is_active() ) {
			$module         = wd_di()->get( Audit_Report::class )->export();
			$module['link'] = network_admin_url( 'admin.php?page=wdf-logging&view=logs' );
			$modules[]      = $module;
		}

		return $modules;
	}

	/**
	 * Get active modules of Pro reports as array of arrays.
	 *
	 * @return array
	 */
	public function get_active_pro_reports() {
		$modules = array();
		// Malware_Report.
		if ( true === wd_di()->get( \WP_Defender\Model\Setting\Scan::class )->scheduled_scanning ) {
			$modules[] = wd_di()->get( Malware_Report::class )->export();
		}
		// Firewall_Report.
		$modules[] = wd_di()->get( Firewall_Report::class )->export();
		// Audit_Report.
		if ( true === wd_di()->get( Audit_Logging::class )->is_active() ) {
			$modules[] = wd_di()->get( Audit_Report::class )->export();
		}

		return $modules;
	}

	/**
	 * Get active modules of Pro reports as array of objects.
	 *
	 * @return array
	 */
	public function get_active_pro_reports_as_objects() {
		$modules = array();
		// Malware_Report.
		if ( true === wd_di()->get( \WP_Defender\Model\Setting\Scan::class )->scheduled_scanning ) {
			$modules[] = wd_di()->get( Malware_Report::class );
		}
		// Firewall_Report.
		$modules[] = wd_di()->get( Firewall_Report::class );
		// Audit_Report.
		if ( true === wd_di()->get( Audit_Logging::class )->is_active() ) {
			$modules[] = wd_di()->get( Audit_Report::class );
		}

		return $modules;
	}

	/**
	 * @return int
	 */
	public function count_active() {
		$count = 0;
		foreach ( $this->get_modules() as $module ) {
			if ( Abstract_Notification::STATUS_ACTIVE === $module['status'] ) {
				++$count;
			}
		}

		return $count;
	}

	public function maybe_dispatch_report() {
		$modules = array( wd_di()->get( Tweak_Reminder::class ) );
		if ( true === $this->is_pro ) {
			$modules = array_merge(
				$modules,
				$this->get_active_pro_reports_as_objects()
			);
		}

		foreach ( $modules as $module ) {
			if ( $module->maybe_send() ) {
				$module->send();
			}
		}
	}

	/**
	 * Get available user roles with user count.
	 *
	 * @return array Return user roles with user count.
	 */
	public function get_user_roles() {
		$user_roles = count_users();

		if ( isset( $user_roles['avail_roles'] ) ) {
			foreach ( $user_roles['avail_roles'] as $key => $value ) {
				if ( 0 === $value ) {
					unset( $user_roles['avail_roles'][ $key ] );
				}
			}
		}

		return $user_roles;
	}
}
