<?php
/**
 * Handles notifications for various WP Defender components such as Firewall, Malware, and Audit Logging.
 *
 * @package WP_Defender\Component
 */

namespace WP_Defender\Component;

use Exception;
use WP_User_Query;
use WP_Defender\Component;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\User;
use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Model\Setting\Audit_Logging;
use WP_Defender\Model\Notification\Audit_Report;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Model\Notification\Tweak_Reminder;
use WP_Defender\Model\Notification\Firewall_Report;
use WP_Defender\Model\Notification\Malware_Notification;
use WP_Defender\Model\Notification\Firewall_Notification;

/**
 * Handles notifications for various WP Defender components such as Firewall, Malware, and Audit Logging.
 */
class Notification extends Component {

	use User;
	use IO;

	/**
	 * Indicates if the current installation is a Pro version.
	 *
	 * @var bool
	 */
	private $is_pro;

	/**
	 * Constructs the Notification component and initializes the WPMUDEV behavior.
	 */
	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
		$this->is_pro = ( new WPMUDEV() )->is_pro();
	}

	/**
	 * Retrieves a pool of users based on specified criteria.
	 *
	 * @param  array  $exclude  User IDs to exclude.
	 * @param  string $role  Role to filter users by.
	 * @param  string $username  Search term for username.
	 * @param  string $order_by  Property to sort by.
	 * @param  string $order  Sort order (ASC or DESC).
	 * @param  int    $limit  Number of users to retrieve.
	 * @param  int    $paged  Page number for pagination.
	 *
	 * @return array Array of user data including display name, email, role, avatar, and ID.
	 */
	public function get_users_pool(
		$exclude = array(),
		$role = '',
		$username = '',
		$order_by = 'ID',
		$order = 'ASC',
		$limit = 15,
		$paged = 1
	): array {
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
		$user_query = new WP_User_Query( $params );

		$pools = array();
		foreach ( $user_query->get_results() as $user ) {
			$pools[] = array(
				'name'   => $this->get_user_display( $user ),
				'email'  => $this->get_current_user_email( $user ),
				'role'   => $this->get_current_user_role( $user ),
				'avatar' => get_avatar_url( $this->get_current_user_email( $user ) ),
				'id'     => $user->ID,
				'status' => \WP_Defender\Model\Notification::USER_SUBSCRIBE_NA,
			);
		}

		return $pools;
	}

	/**
	 * Dispatches notifications based on the module slug and additional arguments.
	 *
	 * @param  string $slug  Module slug to identify the notification handler.
	 * @param  object $args  Additional arguments for the notification.
	 */
	public function dispatch_notification( $slug, $args ) {
		$module = $this->find_module_by_slug( $slug );
		if ( is_object( $module ) ) {
			if ( 'malware-notification' === $module->slug && $module->check_options() ) {
				// Case report.
				$module->send( $args );
			} elseif ( 'firewall-notification' === $module->slug && $module->check_options( $args ) ) {
				$module->send( $args );
			}
		}
	}

	/**
	 * Finds a notification module by its slug.
	 *
	 * @param  string $slug  The slug of the module.
	 *
	 * @return mixed Returns the module object if found.
	 */
	public function find_module_by_slug( $slug ) {
		switch ( $slug ) {
			case Tweak_Reminder::SLUG:
				return wd_di()->get( Tweak_Reminder::class );
			case Malware_Notification::SLUG:
				return wd_di()->get( Malware_Notification::class );
			case Firewall_Notification::SLUG:
				return wd_di()->get( Firewall_Notification::class );
			case Malware_Report::SLUG:
				return wd_di()->get( Malware_Report::class );
			case Firewall_Report::SLUG:
				return wd_di()->get( Firewall_Report::class );
			case Audit_Report::SLUG:
			default:
				return wd_di()->get( Audit_Report::class );
		}
	}

	/**
	 * Send a verification email to users.
	 *
	 * @param  \WP_Defender\Model\Notification $model  Notification model containing recipient details.
	 *
	 * @throws Exception Emits Exception in case of an error.
	 */
	public function send_subscription_confirm_email( \WP_Defender\Model\Notification $model ) {
		foreach ( $model->in_house_recipients as &$subscriber ) {
			if ( empty( $subscriber['status'] ) ) {
				continue;
			}
			if ( \WP_Defender\Model\Notification::USER_SUBSCRIBE_NA !== $subscriber['status'] ) {
				continue;
			}
			$ret = $this->send_email( $subscriber, $model );

			if ( $ret ) {
				$subscriber['status'] = \WP_Defender\Model\Notification::USER_SUBSCRIBE_WAITING;
			}
		}
		foreach ( $model->out_house_recipients as &$subscriber ) {
			if ( empty( $subscriber['status'] ) ) {
				continue;
			}
			if ( \WP_Defender\Model\Notification::USER_SUBSCRIBE_NA !== $subscriber['status'] ) {
				continue;
			}
			$ret = $this->send_email( $subscriber, $model );

			if ( $ret ) {
				$subscriber['status'] = \WP_Defender\Model\Notification::USER_SUBSCRIBE_WAITING;
			}
		}

		$model->save();
	}

	/**
	 * Sends an email to a subscriber.
	 *
	 * @param  array                           $subscriber  Subscriber information.
	 * @param  \WP_Defender\Model\Notification $model  Notification model used for the email.
	 *
	 * @return bool Returns true if the email was sent successfully.
	 */
	public function send_email( $subscriber, \WP_Defender\Model\Notification $model ) {
		$headers = wd_di()->get( Mail::class )->get_headers(
			defender_noreply_email( 'wd_confirm_noreply_email' ),
			'subscription'
		);
		$email   = $subscriber['email'];
		$name    = $subscriber['name'] ?? '';
		$inhouse = false;
		if ( isset( $subscriber['id'] ) ) {
			$inhouse = true;
		}
		$url     = $this->create_subscribe_url( $model->slug, $email, $inhouse );
		$subject = sprintf( /* translators: %s: Model title. */ 'Subscribe to %s', $model->title );
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
				'title'            => preg_replace( '/ - Notification$/', '', $model->title ),
				'content_body'     => $content_body,
				// An empty value because this is a confirmation email.
				'unsubscribe_link' => '',
			),
			false
		);

		// We send email here.
		return wp_mail( $email, $subject, $content, $headers );
	}

	/**
	 * Sends a subscription confirmation email to a user.
	 *
	 * @param  string $email  Email address of the subscriber.
	 * @param  object $m  Notification model object.
	 * @param  string $name  Name of the subscriber.
	 */
	public function send_subscribed_email( $email, $m, $name ) {
		$headers = wd_di()->get( Mail::class )->get_headers(
			defender_noreply_email( 'wd_subscribe_noreply_email' ),
			'subscribe_confimed'
		);

		$notification = wd_di()->get( \WP_Defender\Controller\Notification::class );
		$subject      = esc_html__( 'Confirmed', 'wpdef' );
		$content_body = $notification->render_partial(
			'email/subscribed',
			array(
				'subject'           => esc_html__( 'Subscription Confirmed', 'wpdef' ),
				'notification_name' => $m->title,
				'url'               => $this->create_unsubscribe_url( $m->slug, $email ),
				'name'              => $name,
			)
		);
		$content      = $notification->render_partial(
			'email/index',
			array(
				'title'            => preg_replace( '/ - Notification$/', '', $m->title ),
				'content_body'     => $content_body,
				// An empty value because this is a subscribed email.
				'unsubscribe_link' => '',
			),
			false
		);

		wp_mail( $email, $subject, $content, $headers );
	}

	/**
	 * Sends an unsubscribe email to a user.
	 *
	 * @param  object $m  Notification model object.
	 * @param  string $email  Email address of the subscriber.
	 * @param  bool   $inhouse  Indicates if the user is an in-house user.
	 * @param  string $name  Name of the subscriber.
	 */
	public function send_unsubscribe_email( $m, $email, $inhouse, $name ) {
		$subject = esc_html__( 'Unsubscribed', 'wpdef' );
		$url     = $this->create_subscribe_url( $m->slug, $email, $inhouse );
		// Render emails.
		$notification = wd_di()->get( \WP_Defender\Controller\Notification::class );
		$content_body = $notification->render_partial(
			'email/unsubscribe',
			array(
				'subject'           => esc_html__( 'Unsubscribed', 'wpdef' ),
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
				'title'            => $title,
				'content_body'     => $content_body,
				// An empty value because this is an unsubscribed email.
				'unsubscribe_link' => '',
			),
			false
		);

		$headers = wd_di()->get( Mail::class )->get_headers(
			defender_noreply_email( 'wd_unsubscribe_noreply_email' ),
			'unsubscription'
		);

		wp_mail( $email, $subject, $content, $headers );
	}

	/**
	 * Creates a URL for unsubscribing from notifications.
	 *
	 * @param  string $slug  Notification slug.
	 * @param  string $email  Email address of the subscriber.
	 *
	 * @return string Unsubscribe URL.
	 */
	public function create_unsubscribe_url( $slug, $email ): string {
		return add_query_arg(
			array(
				'action' => 'defender_listen_user_unsubscribe',
				'hash'   => hash( 'sha256', $email . AUTH_SALT ),
				'slug'   => $slug,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * Creates a URL for subscribing to notifications.
	 *
	 * @param  string $slug  Notification slug.
	 * @param  string $email  Email address of the subscriber.
	 * @param  bool   $inhouse  Indicates if the user is an in-house user.
	 *
	 * @return string Subscribe URL.
	 */
	public function create_subscribe_url( $slug, $email, $inhouse ): string {
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
	public function get_modules(): array {
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
	 */
	public function get_modules_as_objects(): array {
		$modules = array(
			wd_di()->get( Tweak_Reminder::class ),
			wd_di()->get( Malware_Notification::class ),
			wd_di()->get( Firewall_Notification::class ),
		);

		if ( true === $this->is_pro ) {
			$modules = array_merge(
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
	 * @return string|null
	 */
	public function get_next_run() {
		if ( false === $this->is_pro ) {
			return esc_html__( 'Never', 'wpdef' );
		}
		$modules  = $this->get_active_pro_reports_as_objects();
		$next_run = null;
		foreach ( $modules as $module ) {
			if ( \WP_Defender\Model\Notification::STATUS_ACTIVE !== $module->status ) {
				continue;
			}
			if ( is_null( $next_run ) ) {
				$next_run = $module;
			} elseif ( $module->est_timestamp < $next_run->est_timestamp ) {
				$next_run = $module;
			}
		}
		if ( is_null( $next_run ) ) {
			return esc_html__( 'Never', 'wpdef' );
		}

		return $next_run->get_next_run_as_string();
	}

	/**
	 * Get inactive modules.
	 *
	 * @return array
	 * @since 2.7.0 Malware Scanning - Reporting may be inactive.
	 */
	public function get_inactive_modules(): array {
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
	public function get_active_pro_reports(): array {
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
	public function get_active_pro_reports_as_objects(): array {
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
	 * Counts the number of active modules.
	 *
	 * @return int Number of active modules.
	 */
	public function count_active(): int {
		$count = 0;
		foreach ( $this->get_modules() as $module ) {
			if ( \WP_Defender\Model\Notification::STATUS_ACTIVE === $module['status'] ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Dispatches reports if conditions are met.
	 */
	public function maybe_dispatch_report() {
		$modules = array( wd_di()->get( Tweak_Reminder::class ) );
		if ( true === $this->is_pro ) {
			$modules = array_merge( $modules, $this->get_active_pro_reports_as_objects() );
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
	public function get_user_roles(): array {
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