<?php

namespace WP_Defender\Component;

use WP_Defender\Behavior\WPMUDEV;
use WP_Defender\Component;
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

	public function __construct() {
		$this->attach_behavior( WPMUDEV::class, WPMUDEV::class );
	}

	/**
	 * @param array $exclude
	 * @param string $role
	 * @param string $username
	 * @param string $order_by
	 * @param string $order
	 * @param int $limit
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
				'status' => \WP_Defender\Model\Notification::USER_SUBSCRIBE_NA,
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
			// case report.
			$module->send( $args );
		} elseif ( 'firewall-notification' === $module->slug && $module->check_options( $args ) ) {
			$module->send( $args );
		}
	}

	/**
	 * @param array $data
	 *
	 * @return bool|\WP_Error
	 */
	public function validate_email( $data ) {
		$subscribers = $data['subscribers'];
		$emails      = wp_list_pluck( $subscribers, 'email' );
		// Validate if those email is from our site.
		foreach ( $emails as $email ) {
			$user = get_user_by( 'email', $email );
			if ( ! is_object( $user ) ) {
				return new \WP_Error( Error_Code::INVALID, __( 'Invalid email address', 'wpdef' ) );
			}
		}
		$is_error = false;
		if ( ! is_array( $data['email_inviters'] ) ) {
			$data['email_inviters'] = array();
		}
		foreach ( $data['email_inviters'] as $key => &$inviter ) {
			if ( empty( trim( $inviter['email'] ) ) ) {
				unset( $data['email_inviters'][ $key ] );
				continue;
			}
			if ( ! filter_var( $inviter['email'], FILTER_VALIDATE_EMAIL ) ) {
				$inviter['error']         = true;
				$inviter['error_message'] = __( 'Invalid email address', 'wpdef' );
				$is_error                 = true;
			} elseif ( in_array( $inviter['email'], $emails, true ) ) {
				$inviter['error']         = true;
				$inviter['error_message'] = __( 'This email address is already in use', 'wpdef' );
				$is_error                 = true;
			}
		}

		if ( $is_error ) {
			return $data;
		}

		return true;
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
	 * @param \WP_Defender\Model\Notification $model
	 * @param                                 $routes
	 */
	public function send_subscription_confirm_email( \WP_Defender\Model\Notification $model, $routes ) {
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
	 * @param array                           $subscriber
	 * @param \WP_Defender\Model\Notification $model
	 *
	 * @return bool
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function send_email( $subscriber, \WP_Defender\Model\Notification $model ) {
		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_confirm_noreply_email' )
		);

		$email   = $subscriber['email'];
		$inhouse = false;
		if ( isset( $subscriber['id'] ) ) {
			$inhouse = true;
		}
		$url     = add_query_arg(
			array(
				'action'  => 'defender_listen_user_subscribe',
				'hash'    => hash( 'sha256', $email . AUTH_SALT ),
				'uid'     => $model->slug,
				'inhouse' => $inhouse,
			),
			admin_url( 'admin-ajax.php' )
		);
		$subject = sprintf( 'Subscribe to %s', $model->title );

		// We send email here.
		return wp_mail(
			$email,
			$subject,
			wd_di()->get( \WP_Defender\Controller\Notification::class )->render_partial(
				'email/confirm',
				array(
					'subject'           => $subject,
					'email'             => $email,
					'notification_name' => $model->title,
					'url'               => $url,
				),
				false
			),
			$headers
		);
	}

	/**
	 * @param string $email
	 * @param object $m
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 */
	public function send_subscribed_email( $email, $m ) {
		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_subscribe_noreply_email' )
		);

		$subject  = __( 'Confirmed', 'wpdef' );
		$template = wd_di()->get( \WP_Defender\Controller\Notification::class )->render_partial(
			'email/subscribed',
			array(
				'title' => $m->title,
				'url'   => $this->create_unsubscribe_url( $m, $email ),
			)
		);

		wp_mail( $email, $subject, $template, $headers );
	}

	public function send_unsubscribe_email( $m, $email, $inhouse ) {
		$subject  = __( 'Unsubscribed', 'wpdef' );
		$url      = add_query_arg(
			array(
				'action'  => 'defender_listen_user_subscribe',
				'hash'    => hash( 'sha256', $email . AUTH_SALT ),
				'uid'     => $m->slug,
				'inhouse' => $inhouse,
			),
			admin_url( 'admin-ajax.php' )
		);
		$template = wd_di()->get( \WP_Defender\Controller\Notification::class )->render_partial(
			'email/unsubscribe',
			array(
				'title' => $m->title,
				'url'   => $url,
			)
		);

		$headers = defender_noreply_html_header(
			defender_noreply_email( 'wd_unsubscribe_noreply_email' )
		);

		wp_mail( $email, $subject, $template, $headers );
	}

	/**
	 * @param object $module
	 * @param string $email
	 *
	 * @return string
	 */
	public function create_unsubscribe_url( $module, $email ) {
		$list = wd_di()->get( \WP_Defender\Controller\Notification::class )->dump_routes_and_nonces();

		return add_query_arg(
			array(
				'_def_nonce' => $list['nonces']['unsubscribe_and_send_email'],
				'route'      => $list['routes']['unsubscribe_and_send_email'],
				'action'     => 'wp_defender/v1/hub/',
				'slug'       => $module->slug,
				'hash'       => hash( 'sha256', $email . AUTH_SALT ),
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * @param string $slug
	 * @param string $email
	 *
	 * @return string
	 */
	public function create_subscribe_url( $slug, $email ) {
		return add_query_arg(
			array(
				'action' => 'defender_listen_user_subscribe',
				'hash'   => hash( 'sha256', $email . AUTH_SALT ),
				'uid'    => $slug,
			),
			admin_url( 'admin-ajax.php' )
		);
	}

	/**
	 * @return array
	 */
	public function get_modules() {
		$modules = array(
			wd_di()->get( Tweak_Reminder::class )->export(),
			wd_di()->get( Malware_Notification::class )->export(),
			wd_di()->get( Firewall_Notification::class )->export(),
		);

		if ( true === $this->is_pro() ) {
			$modules = array_merge(
				$modules,
				array(
					wd_di()->get( Malware_Report::class )->export(),
					wd_di()->get( Firewall_Report::class )->export(),
				)
			);
			if ( 0 === count( $this->get_inactive_modules() ) ) {
				$modules[] = wd_di()->get( Audit_Report::class )->export();
			}
		}

		return $modules;
	}

	/**
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

		if ( true === $this->is_pro() ) {
			$modules   = array_merge(
				$modules,
				array(
					wd_di()->get( Malware_Report::class ),
					wd_di()->get( Firewall_Report::class ),
				)
			);
			$modules[] = wd_di()->get( Audit_Report::class );
		}

		return $modules;
	}

	/**
	 * Return the time that next report will be trigger.
	 *
	 * @return false|string|void
	 */
	public function get_next_run() {
		if ( false === $this->is_pro() ) {
			return __( 'Never', 'wpdef' );
		}
		$modules = array(
			wd_di()->get( Malware_Report::class ),
			wd_di()->get( Firewall_Report::class ),
		);
		if ( count( $this->get_inactive_modules() ) === 0 ) {
			$modules[] = wd_di()->get( Audit_Report::class );
		}
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
			return __( 'Never', 'wpdef' );
		}

		return $next_run->get_next_run_as_string();
	}

	/**
	 * At the moment, only audit can be turn off completely.
	 * @return array
	 */
	public function get_inactive_modules() {
		//Todo: add logic for deactivated scan settings
		if ( false === $this->is_pro() ) {
			return array();
		}
		if ( false === wd_di()->get( Audit_Logging::class )->is_active() ) {
			return array( wd_di()->get( Audit_Report::class )->export() );
		}

		return array();
	}

	/**
	 * @return int
	 */
	public function count_active() {
		$count = 0;
		foreach ( $this->get_modules() as $module ) {
			if ( \WP_Defender\Model\Notification::STATUS_ACTIVE === $module['status'] ) {
				++$count;
			}
		}

		return $count;
	}

	public function maybe_dispatch_report() {
		$modules = array(
			wd_di()->get( Tweak_Reminder::class ),
		);
		if ( true === $this->is_pro() ) {
			$pro_modules = array(
				wd_di()->get( Malware_Report::class ),
				wd_di()->get( Firewall_Report::class ),
			);
			if ( true === wd_di()->get( Audit_Logging::class )->is_active() ) {
				$pro_modules[] = wd_di()->get( Audit_Report::class );
			}
			$modules = array_merge(
				$modules,
				$pro_modules
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
