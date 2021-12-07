<?php

namespace WP_Defender\Controller;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\HTTP;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Controller2;
use WP_Defender\Traits\Formats;
use WP_Defender\Traits\User;
use WP_Defender\Model\Notification as Model_Notification;

class Notification extends Controller2 {
	use User, Formats;

	public $slug = 'wdf-notification';

	/**
	 * @var \WP_Defender\Component\Notification
	 */
	protected $service;

	public function __construct() {
		$this->register_page(
			esc_html__( 'Notifications', 'wpdef' ),
			$this->slug,
			array(
				&$this,
				'main_view',
			),
			$this->parent_slug
		);
		$this->register_routes();
		$this->service = wd_di()->get( \WP_Defender\Component\Notification::class );
		add_action( 'defender_enqueue_assets', array( &$this, 'enqueue_assets' ) );
		// We use custom ajax endpoint here as the nonce would fail with other user.
		add_action( 'wp_ajax_defender_listen_user_subscribe', array( &$this, 'verify_subscriber' ) );
		add_action( 'wp_ajax_nopriv_defender_listen_user_subscribe', array( &$this, 'verify_subscriber' ) );
		add_action( 'defender_notify', array( &$this, 'send_notify' ), 10, 2 );
		add_filter( 'cron_schedules', array( &$this, 'add_cron_schedules' ) );
		// We will schedule the time to send reports.
		if ( ! wp_next_scheduled( 'wdf_maybe_send_report' ) ) {
			$timestamp = gmmktime( gmdate( 'H' ), 0, 0 );
			wp_schedule_event( $timestamp, 'thirty_minutes', 'wdf_maybe_send_report' );
		}
		add_action( 'wdf_maybe_send_report', array( &$this, 'report_sender' ) );
		add_action( 'admin_notices', array( &$this, 'show_subscribed_confirmation' ) );
	}

	/**
	 * Add a new cron schedule to send reports.
	 *
	 * @param array $schedules
	 *
	 * @return array $schedules
	 */
	public function add_cron_schedules( $schedules ) {
		$schedules['thirty_minutes'] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => esc_html__( 'Every Half Hour', 'wpdef' ),
		);
		return $schedules;
	}

	/**
	 * @return null
	 */
	public function show_subscribed_confirmation() {
		if ( ! defined( 'IS_PROFILE_PAGE' ) || false === constant( 'IS_PROFILE_PAGE' ) ) {
			return null;
		}
		$slug = isset( $_GET['slug'] ) ? sanitize_text_field( $_GET['slug'] ) : false;
		if ( empty( $slug ) ) {
			return null;
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			return null;
		}
		$context = isset( $_GET['context'] ) ? sanitize_text_field( $_GET['context'] ) : false;
		if ( 'subscribed' === $context ) {
			$unsubscribe_link = $this->service->create_unsubscribe_url( $m, $this->get_current_user_email() );
			$strings          = sprintf(
			/* translators: %s - module title, %s - unsubscribed link */
				__( 'You are now subscribed to receive <strong>%1$s</strong>. Made a mistake? <a href="%2$s">Unsubscribe</a>', 'wpdef' ),
				$m->title,
				$unsubscribe_link
			);
		} elseif ( 'unsubscribe' === $context ) {
			$strings = sprintf(
			/* translators: %s - module title */
				__( 'You are now unsubscribed from <strong>%s</strong>.', 'wpdef' ),
				$m->title
			);
		} else {
			return null;
		}
		?>
		<div class="notice notice-success" style="position:relative;">
			<p><?php echo $strings; ?></p>
			<a href="<?php echo get_edit_profile_url(); ?>" class="notice-dismiss" style="text-decoration: none">
				<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'wpdef' ); ?></span>
			</a>
		</div>
		<?php
	}

	/**
	 * Trigger report check signals.
	 */
	public function report_sender() {
		$this->service->maybe_dispatch_report();
	}

	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Dispatch notification.
	 *
	 * @param string $slug
	 * @param object $args
	 */
	public function send_notify( $slug, $args ) {
		$this->service->dispatch_notification( $slug, $args );
	}

	/**
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function validate_email( Request $request ) {
		$data  = $request->get_data(
			array(
				'email' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$email = isset( $data['email'] ) ? $data['email'] : false;
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return new Response(
				true,
				array(
					'error'  => false,
					'avatar' => get_avatar_url( $data['email'] ),
				)
			);
		} else {
			return new Response(
				false,
				array(
					'error' => __( 'Invalid email address', 'wpdef' ),
				)
			);
		}
	}


	/**
	 * @param Request $request
	 *
	 * @defender_route
	 * @is_public
	 * @return Response
	 */
	public function unsubscribe_and_send_email( Request $request ) {
		$slug = HTTP::get( 'slug', '' );
		$hash = HTTP::get( 'hash', '' );
		$slug = sanitize_text_field( $slug );
		if ( empty( $slug ) || empty( $hash ) ) {
			wp_die( __( 'Invalid request', 'wpdef' ) );
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			wp_die( __( 'Invalid request', 'wpdef' ) );
		}
		$inhouse = false;
		foreach ( $m->in_house_recipients as &$recipient ) {
			$email = $recipient['email'];
			if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
				if ( ! is_user_logged_in() ) {
					auth_redirect();
				}
				if ( $email !== $this->get_current_user_email() ) {
					wp_die( __( 'Invalid request', 'wpdef' ) );
				}
				$recipient['status'] = Model_Notification::USER_SUBSCRIBE_CANCELED;
				$m->save();
				$inhouse = true;
				//send email
				$this->service->send_unsubscribe_email( $m, $email, $inhouse );
				break;
			}
		}

		if ( false === $inhouse ) {
			// No match on in-house, check the outhouse list.
			foreach ( $m->out_house_recipients as &$recipient ) {
				$email = $recipient['email'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
					$recipient['status'] = Model_Notification::USER_SUBSCRIBE_CANCELED;
					$m->save();
					$this->service->send_unsubscribe_email( $m, $email, $inhouse );
				}
			}
		}
		if ( $inhouse ) {
			wp_redirect(
				add_query_arg(
					array(
						'slug'    => $slug,
						'context' => 'unsubscribe',
					),
					get_edit_profile_url()
				)
			);
		} else {
			wp_redirect( get_home_url() );
		}
		exit;
	}

	/**
	 * An endpoint for saving single config from frontend.
	 *
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function save( Request $request ) {
		$raw_data = $request->get_data();
		$slug     = sanitize_textarea_field( $raw_data['slug'] );
		$model    = $this->service->find_module_by_slug( $slug );

		if ( ! is_object( $model ) ) {
			// Should never be here.
			die;
		}
		$data = $request->get_data_by_model( $model );
		$model->import( $data );
		$model->status = Model_Notification::STATUS_ACTIVE;
		if ( $model->validate() ) {
			if ( 0 === $model->last_sent ) {
				// This means that the notification or report never sent, we will use the moment that it get activate.
				$model->last_sent = time();
			}
			$model->save();
			$this->service->send_subscription_confirm_email(
				$model,
				$this->dump_routes_and_nonces()
			);
			Config_Hub_Helper::set_clear_active_flag();

			return new Response(
				true,
				array_merge(
					array(
						'message' => __(
							'You have activated the notification successfully. Note, recipients will need to confirm their subscriptions to begin receiving notifications.',
							'wpdef'
						),
					),
					$this->data_frontend()
				)
			);
		}

		return new Response(
			false,
			array(
				'message' => $model->get_formatted_errors(),
			)
		);
	}

	/**
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function save_bulk( Request $request ) {
		$data = $request->get_data(
			array(
				'reports'       => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_textarea_field',
				),
				'notifications' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_textarea_field',
				),
			)
		);
		$this->save_reports( $data['reports'] );
		$this->save_notifications( $data['notifications'] );
		Config_Hub_Helper::set_clear_active_flag();

		return new Response(
			true,
			array_merge(
				$this->data_frontend(),
				array(
					'message' => __(
						'Your settings have been updated successfully. Any new recipients will receive an email to confirm their subscription.',
						'wpdef'
					),
				)
			)
		);
	}

	/**
	 * Process bulk reports saving.
	 *
	 * @param array $data
	 */
	private function save_reports( $data ) {
		foreach ( $data['configs'] as $datum ) {
			$slug  = $datum['slug'];
			$model = $this->service->find_module_by_slug( $slug );
			if ( ! is_object( $model ) ) {
				continue;
			}

			$import = array(
				// Bulk saving must always enabled.
				'status'               => Model_Notification::STATUS_ACTIVE,
				'configs'              => $datum,
				'in_house_recipients'  => $data['in_house_recipients'],
				'out_house_recipients' => $data['out_house_recipients'],
				'day'                  => $data['day'],
				'time'                 => $data['time'],
				'frequency'            => $data['frequency'],
				'day_n'                => $data['day_n'],
			);
			foreach ( $import['out_house_recipients'] as $key => $val ) {
				if ( ! filter_var( $val['email'], FILTER_VALIDATE_EMAIL ) ) {
					unset( $import['out_house_recipients'][ $key ] );
				}
			}
			$model->import( $import );
			if ( $model->validate() ) {
				$model->save();
				$this->service->send_subscription_confirm_email(
					$model,
					$this->dump_routes_and_nonces()
				);
			}
		}
	}

	/**
	 * @param array $data
	 *
	 * @throws \DI\DependencyException
	 * @throws \DI\NotFoundException
	 * @throws \ReflectionException
	 */
	private function save_notifications( $data ) {
		foreach ( $data['configs'] as $datum ) {
			$slug  = $datum['slug'];
			$model = $this->service->find_module_by_slug( $slug );
			if ( ! is_object( $model ) ) {
				continue;
			}
			$import = array(
				'status'               => Model_Notification::STATUS_ACTIVE,
				'configs'              => $datum,
				'in_house_recipients'  => $data['in_house_recipients'],
				'out_house_recipients' => $data['out_house_recipients'],
			);
			foreach ( $import['out_house_recipients'] as $key => $val ) {
				if ( ! filter_var( $val['email'], FILTER_VALIDATE_EMAIL ) ) {
					unset( $import['out_house_recipients'][ $key ] );
				}
			}
			$model->import( $import );
			if ( $model->validate() ) {
				$model->save();
				$this->service->send_subscription_confirm_email(
					$model,
					$this->dump_routes_and_nonces()
				);
			}
		}
	}

	/**
	 * Bulk activate.
	 *
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 * @throws \Exception
	 */
	public function bulk_activate( Request $request ) {
		$data  = $request->get_data(
			array(
				'slugs' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slugs = $data['slugs'];
		if ( empty( $slugs ) ) {
			return new Response( false, array() );
		}

		foreach ( $slugs as $slug ) {
			$model = $this->service->find_module_by_slug( $slug );
			if ( is_object( $model ) ) {
				$model->status = Model_Notification::STATUS_ACTIVE;
				if ( 0 === $model->last_sent ) {
					// This means that the notification or report never sent, we will use the moment that it get activate.
					$model->last_sent = time();
				}
				$model->save();
			}
		}

		return new Response(
			true,
			array_merge(
				array(
					'message' => 'You have activated the notification successfully. Note, recipients will need to confirm their subscriptions to begin receiving notifications.',
				),
				$this->data_frontend()
			)
		);
	}

	/**
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function bulk_deactivate( Request $request ) {
		$data  = $request->get_data(
			array(
				'slugs' => array(
					'type'     => 'array',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$slugs = $data['slugs'];
		if ( empty( $slugs ) ) {
			return new Response( false, array() );
		}

		foreach ( $slugs as $slug ) {
			$model = $this->service->find_module_by_slug( $slug );
			if ( is_object( $model ) ) {
				$model->status = Model_Notification::STATUS_DISABLED;
				$model->save();
			}
		}

		return new Response(
			true,
			array_merge(
				array(
					'message' => __( 'You have deactivated the notifications successfully.', 'wpdef' ),
				),
				$this->data_frontend()
			)
		);
	}

	/**
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function disable( Request $request ) {
		$data = $request->get_data(
			array(
				'slug' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$slug  = $data['slug'];
		$model = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $model ) ) {
			// Should never be here.
			die;
		}
		$model->status = Model_Notification::STATUS_DISABLED;
		$model->save();

		return new Response(
			true,
			array_merge(
				$this->data_frontend(),
				array(
					'message' => __( 'You have deactivated the notification successfully.', 'wpdef' ),
				)
			)
		);
	}

	/**
	 * This is a receiver to process subscribe confirmation from email.
	 */
	public function verify_subscriber() {
		$hash    = HTTP::get( 'hash', false );
		$slug    = HTTP::get( 'uid', false );
		$inhouse = HTTP::get( 'inhouse', 0 );
		if ( $inhouse && ! is_user_logged_in() ) {
			// This is in-house, so we need to redirect.
			auth_redirect();
		}
		if ( false === $hash || false === $slug ) {
			wp_die( __( 'You shall not pass', 'wpdef' ) );
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			wp_die( __( 'You shall not pass', 'wpdef' ) );
		}
		if ( $inhouse ) {
			$processed = false;
			foreach ( $m->in_house_recipients as &$recipient ) {
				if ( Model_Notification::USER_SUBSCRIBED === $recipient['status'] ) {
					continue;
				}
				$email = $recipient['email'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) )
					&& $email === $this->get_current_user_email() ) {
					$recipient['status'] = Model_Notification::USER_SUBSCRIBED;
					$this->service->send_subscribed_email( $email, $m );
					$processed = true;
				}
			}
		} else {
			foreach ( $m->out_house_recipients as &$recipient ) {
				if ( Model_Notification::USER_SUBSCRIBED === $recipient['status'] ) {
					continue;
				}
				$email = $recipient['email'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
					$recipient['status'] = Model_Notification::USER_SUBSCRIBED;
					$this->service->send_subscribed_email( $email, $m );
				}
			}
		}
		$m->save();
		if ( $inhouse ) {
			if ( $processed ) {
				wp_redirect(
					add_query_arg(
						array(
							'slug'    => $m->slug,
							'context' => 'subscribed',
						),
						get_edit_profile_url()
					)
				);
			} else {
				wp_redirect( home_url() );
			}
		} else {
			wp_redirect( home_url() );
		}
		exit;
	}

	/**
	 * Enqueue assets & output data.
	 */
	public function enqueue_assets() {
		if ( ! $this->is_page_active() ) {
			return;
		}
		wp_localize_script(
			'def-notification',
			'notification',
			array_merge( $this->data_frontend(), $this->dump_routes_and_nonces() )
		);
		wp_enqueue_script( 'def-momentjs', defender_asset_url( '/assets/js/vendor/moment/moment.min.js' ) );
		wp_enqueue_script( 'def-notification' );
		$this->enqueue_main_assets();
		wp_enqueue_style(
			'def-select2',
			defender_asset_url( '/assets/css/select2.min.css' )
		);
	}

	/**
	 * An endpoint for fetching users pool.
	 *
	 * @param Request $request Request data.
	 *
	 * @defender_route
	 */
	public function get_users( Request $request ) {
		$data     = $request->get_data(
			array(
				'paged'            => array(
					'type'     => 'int',
					'sanitize' => 'sanitize_text_field',
				),
				'search'           => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'exclude'          => array(
					'type' => 'array',
				),
				'module'           => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'user_role_filter' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'user_sort'        => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$paged    = 1;
		$exclude  = isset( $data['exclude'] ) ? $data['exclude'] : array();
		$username = isset( $data['search'] ) ? $data['search'] : '';
		$slug     = isset( $data['module'] ) ? $data['module'] : null;
		$role     = '';

		if (
			isset( $data['user_role_filter'] ) &&
			'all' !== $data['user_role_filter']
		) {
			$role = $data['user_role_filter'];
		}

		$order_by = 'ID';
		$order    = 'DESC';
		if ( isset( $data['user_sort'] ) ) {
			switch ( $data['user_sort'] ) {
				case 'recent':
					$order_by = 'registered';
					$order    = 'DESC';
					break;
				case 'alpha_asc':
					$order_by = 'display_name';
					$order    = 'ASC';
					break;
				case 'alpha_desc':
				default:
					$order_by = 'display_name';
					$order    = 'DESC';
					break;
			}
		}

		if ( strlen( $username ) ) {
			$username = "*$username*";
		}

		$users = $this->service->get_users_pool(
			$exclude,
			$role,
			$username,
			$order_by,
			$order,
			10,
			$paged
		);

		if ( ! is_null( $slug ) ) {
			$notification = $this->service->find_module_by_slug( $slug );
			if ( is_object( $notification ) ) {
				foreach ( $notification->in_house_recipients as $recipient ) {
					foreach ( $users as &$user ) {
						if ( $user['email'] === $recipient['email'] ) {
							$user['status'] = $recipient['status'];
						}
					}
				}
			}
		}

		wp_send_json_success( $users );
	}

	public function remove_settings() {
		foreach ( $this->service->get_modules_as_objects() as $module ) {
			$module->delete();
		}
	}

	public function remove_data() {}

	public function to_array() {}

	/**
	 * All the variables that we will show on frontend, both in the main page, or dashboard widget.
	 *
	 * @return array
	 */
	public function data_frontend() {
		return array(
			'notifications'          => $this->service->get_modules(),
			'inactive_notifications' => $this->service->get_inactive_modules(),
			'active_count'           => $this->service->count_active(),
			'next_run'               => $this->service->get_next_run(),
			'misc'                   => array(
				'days_of_week'      => $this->get_days_of_week(),
				'times_of_day'      => $this->get_times(),
				'timezone_text'     => sprintf(
				/* translators: %s - timezone, %s - time */
					__(
						'Your timezone is set to <strong>%1$s</strong>, so your current time is <strong>%2$s</strong>.',
						'wpdef'
					),
					wp_timezone_string(),
					date( 'H:i', current_time( 'timestamp' ) )// phpcs:ignore
				),
				'default_recipient' => $this->get_default_recipient(),
			),
		);
	}

	/**
	 * Import the data of other source into this, it can be when HUB trigger the import, or user apply a preset.
	 *
	 * @param array $data
	 */
	public function import_data( $data ) {}

	/**
	 * @return array
	 */
	public function export_strings() {
		$modules = wd_di()->get( Notification::class )->service->get_modules_as_objects();
		$strings = array();
		foreach ( $modules as $module ) {
			/* translators: %s - module title, %s - module status */
			$string = __( '%1$s: %2$s', 'wpdef' );
			if ( 'notification' === $module->type ) {
				$string = sprintf(
					$string,
					$module->title,
					Model_Notification::STATUS_ACTIVE === $module->status ? __( 'Enabled', 'wpdef' ) : __( 'Disabled', 'wpdef' )
				);
			} else {
				$string = sprintf(
					$string,
					$module->title,
					Model_Notification::STATUS_ACTIVE === $module->status ? $module->to_string() : __( 'Disabled', 'wpdef' )
				);
			}
			$strings[] = $string;
		}

		return $strings;
	}

	/**
	 * Resend invite email.
	 *
	 * @param Request $request
	 *
	 * @defender_route
	 * @return Response
	 */
	public function resend_invite_email( Request $request ) {
		$data = $request->get_data( [
			'slug'  => [
				'type'     => 'string',
				'sanitize' => 'sanitize_textarea_field',
			],
			'email' => [
				'type'     => 'string',
				'sanitize' => 'sanitize_text_field',
			],
			'id'    => [
				'type' => 'integer',
			],
		] );

		$model = $this->service->find_module_by_slug( $data['slug'] );

		if ( ! is_object( $model ) ) {
			return new Response( false, [
				'message' => __( 'Module not found.', 'wpdef' ),
			] );
		}

		$subscriber = [ 'email' => $data['email'], ];

		if ( ! empty( $data['id'] ) ) {
			$subscriber['id'] = $data['id'];
		}

		// Resend invite email now.
		$sent = $this->service->send_email( $subscriber, $model );

		if ( $sent ) {
			return new Response( true, [
				'message' => __( 'Invitation sent successfully.', 'wpdef' ),
			] );
		}

		return new Response( false, [
			'message' => __( 'Sorry! We could not send the invitation, Please try again later.', 'wpdef' ),
		] );
	}

	/**
	 * Get user roles with count.
	 *
	 * @defender_route
	 */
	public function get_user_roles() {
		$user_roles = $this->service->get_user_roles();

		wp_send_json_success( $user_roles );
	}
}
