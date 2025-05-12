<?php
/**
 * Handles notification operations.
 *
 * @package WP_Defender\Controller
 */

namespace WP_Defender\Controller;

use Exception;
use WP_Defender\Event;
use Calotes\Helper\HTTP;
use WP_Defender\Traits\User;
use Calotes\Component\Request;
use WP_Defender\Traits\Formats;
use Calotes\Component\Response;
use WP_Defender\Model\Notification\Malware_Report;
use WP_Defender\Component\Config\Config_Hub_Helper;
use WP_Defender\Model\Notification as Model_Notification;

/**
 * Methods for handling notifications.
 */
class Notification extends Event {

	use User;
	use Formats;

	/**
	 * Slug identifier for subscribe page.
	 *
	 * @var string
	 */
	public const SLUG_SUBSCRIBE = 'defender_listen_user_subscribe';

	/**
	 * Slug identifier for unsubscribe page.
	 *
	 * @var string
	 */
	public const SLUG_UNSUBSCRIBE = 'defender_listen_user_unsubscribe';
	/**
	 * The slug identifier for this controller.
	 *
	 * @var string
	 */
	public $slug = 'wdf-notification';

	/**
	 * Service for handling logic.
	 *
	 * @var \WP_Defender\Component\Notification
	 */
	protected $service;

	/**
	 * Initializes the model and service, registers routes, and sets up scheduled events if the model is active.
	 */
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
		add_action( 'wp_ajax_' . self::SLUG_SUBSCRIBE, array( &$this, 'verify_subscriber' ) );
		add_action( 'wp_ajax_nopriv_' . self::SLUG_SUBSCRIBE, array( &$this, 'verify_subscriber' ) );
		add_action( 'wp_ajax_' . self::SLUG_UNSUBSCRIBE, array( &$this, 'unsubscribe_and_send_email' ) );
		add_action( 'wp_ajax_nopriv_' . self::SLUG_UNSUBSCRIBE, array( &$this, 'unsubscribe_and_send_email' ) );
		add_action( 'defender_notify', array( &$this, 'send_notify' ), 10, 2 );
		// We will schedule the time to send reports.
		if ( ! wp_next_scheduled( 'wdf_maybe_send_report' ) ) {
			$timestamp = gmmktime( wp_date( 'H' ), 0, 0 );
			wp_schedule_event( $timestamp, 'thirty_minutes', 'wdf_maybe_send_report' );
		}
		add_action( 'wdf_maybe_send_report', array( &$this, 'report_sender' ) );
		add_action( 'admin_notices', array( &$this, 'show_actions_with_subscription' ) );
	}

	/**
	 * For users who have subscribed or unsubscribed confirmation.
	 *
	 * @return null|void
	 */
	public function show_actions_with_subscription() {
		if ( ! defined( 'IS_PROFILE_PAGE' ) || false === constant( 'IS_PROFILE_PAGE' ) ) {
			return null;
		}
		$slug = defender_get_data_from_request( 'slug', 'g' );
		if ( empty( $slug ) ) {
			return null;
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			return null;
		}
		$context = defender_get_data_from_request( 'context', 'g' );
		if ( 'subscribed' === $context ) {
			$unsubscribe_link = $this->service->create_unsubscribe_url( $m->slug, $this->get_current_user_email() );
			$strings          = sprintf(
			/* translators: 1. Module title. 2. Unsubscribed link. */
				esc_html__(
					'You are now subscribed to receive %1$s. Made a mistake? %2$s',
					'wpdef'
				),
				'<strong>' . $m->title . '</strong>',
				'<a href="' . esc_url_raw( $unsubscribe_link ) . '" style="text-decoration: none">' . esc_html__( 'Unsubscribe', 'wpdef' ) . '</a>'
			);
		} elseif ( 'unsubscribe' === $context ) {
			$strings = sprintf(
			/* translators: %s: Module title. */
				esc_html__( 'You are now unsubscribed from %s.', 'wpdef' ),
				'<strong>' . $m->title . '</strong>'
			);
		} else {
			return null;
		}
		?>
		<div class="notice notice-success" style="position:relative;">
			<p><?php echo wp_kses_post( $strings ); ?></p>
			<a href="<?php echo esc_url_raw( get_edit_profile_url() ); ?>" class="notice-dismiss"
				style="text-decoration: none">
				<span class="screen-reader-text"><?php esc_attr_e( 'Dismiss this notice.', 'wpdef' ); ?></span>
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

	/**
	 * Renders the main view for this page.
	 */
	public function main_view() {
		$this->render( 'main' );
	}

	/**
	 * Dispatch notification.
	 *
	 * @param  string $slug  Module slug to identify the notification handler.
	 * @param  object $args  The arguments to pass to the notification.
	 */
	public function send_notify( $slug, $args ) {
		$this->service->dispatch_notification( $slug, $args );
	}

	/**
	 * Validates an email address provided in the request data.
	 *
	 * @param  Request $request  The request object .The request object containing the data to validate.
	 *
	 * @return Response The response object indicating the validation result.
	 * @defender_route
	 */
	public function validate_email( Request $request ): Response {
		$data  = $request->get_data(
			array(
				'email' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);
		$email = $data['email'] ?? false;
		if ( filter_var( $email, FILTER_VALIDATE_EMAIL ) ) {
			return new Response(
				true,
				array(
					'error'  => false,
					'avatar' => get_avatar_url( $data['email'] ),
				)
			);
		} else {
			return new Response( false, array( 'error' => esc_html__( 'Invalid email address.', 'wpdef' ) ) );
		}
	}

	/**
	 * Unsubscribe process.
	 */
	public function unsubscribe_and_send_email() {
		$slug = HTTP::get( 'slug', '' );
		$hash = HTTP::get( 'hash', '' );
		$slug = sanitize_text_field( $slug );
		if ( empty( $slug ) || empty( $hash ) ) {
			wp_die( esc_html__( 'You shall not pass.', 'wpdef' ) );
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			wp_die( esc_html__( 'You shall not pass.', 'wpdef' ) );
		}
		$inhouse = false;
		foreach ( $m->in_house_recipients as &$recipient ) {
			$email = $recipient['email'];
			if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
				// We skip even an un-logged user, because the admin can change the user's access without notice.
				if ( is_user_logged_in() ) {
					if ( $email !== $this->get_current_user_email() ) {
						wp_die( esc_html__( 'Invalid request.', 'wpdef' ) );
					}
					$inhouse = true;
				}
				$recipient['status'] = Model_Notification::USER_SUBSCRIBE_CANCELED;
				$m->save();
				// Send email.
				$this->service->send_unsubscribe_email( $m, $email, $inhouse, $recipient['name'] );
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
					$this->service->send_unsubscribe_email( $m, $email, $inhouse, $recipient['name'] );
				}
			}
		}
		if ( $inhouse ) {
			wp_safe_redirect(
				add_query_arg(
					array(
						'slug'    => $slug,
						'context' => 'unsubscribe',
					),
					get_edit_profile_url()
				)
			);
		} else {
			wp_safe_redirect( get_home_url() );
		}
		exit;
	}

	/**
	 * An endpoint for saving single config from frontend.
	 *
	 * @param  Request $request  The request object .The request object containing the data to save.
	 *
	 * @defender_route
	 * @return Response
	 * @throws Exception Emits Exception in case of an error.
	 */
	public function save( Request $request ): Response {
		$raw_data = $request->get_data();
		if ( empty( $raw_data['slug'] ) ) {
			return new Response( false, array( 'message' => esc_html__( 'Invalid data.', 'wpdef' ) ) );
		}
		$slug  = sanitize_textarea_field( $raw_data['slug'] );
		$model = $this->service->find_module_by_slug( $slug );

		if ( ! is_object( $model ) ) {
			return new Response( false, array( 'message' => esc_html__( 'Invalid data.', 'wpdef' ) ) );
		}
		$data = $request->get_data_by_model( $model );
		// Check config-values.
		$data['configs'] = $model->type_casting( $data['configs'] );

		$model->import( $data );
		$model->status = Model_Notification::STATUS_ACTIVE;
		if ( $model->validate() ) {
			if ( 0 === $model->last_sent ) {
				// This means that the notification or report never sent, we will use the moment that it get activate.
				$model->last_sent = time();
			}
			$model->save();
			$this->service->send_subscription_confirm_email( $model );
			Config_Hub_Helper::set_clear_active_flag();
			// Track.
			if ( $this->is_tracking_active() ) {
				$track_data = array( 'Notification type' => $raw_data['title'] );
				// For reports. Separated check for 'Security Recommendations - Notification'.
				if ( 'report' === $raw_data['type'] ) {
					$track_data['Notification schedule'] = ucfirst( $data['frequency'] );
				} elseif ( 'tweak-reminder' === $raw_data['slug'] ) {
					$track_data['Notification schedule'] = ucfirst( $data['configs']['reminder'] );
				}
				$this->track_feature( 'def_notification_activated', $track_data );
			}

			return new Response(
				true,
				array_merge(
					array(
						'message' => esc_html__(
							'You have activated the notification successfully. Note, recipients will need to confirm their subscriptions to begin receiving notifications.',
							'wpdef'
						),
					),
					$this->data_frontend()
				)
			);
		}

		return new Response( false, array( 'message' => $model->get_formatted_errors() ) );
	}

	/**
	 * Bulk update and save changes.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function save_bulk( Request $request ): Response {
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
					'message' => esc_html__(
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
	 * @param  array $data  Data to save.
	 *
	 * @throws Exception Emits Exception in case of an error.
	 */
	private function save_reports( $data ) {
		foreach ( $data['configs'] as $datum ) {
			$slug  = $datum['slug'];
			$model = $this->service->find_module_by_slug( $slug );
			if ( ! is_object( $model ) ) {
				continue;
			}

			$import = array(
				// Saving after Bulk-Update must always change the status to Active.
				'status'               => Model_Notification::STATUS_ACTIVE,
				'configs'              => $model->type_casting( $datum ),
				'in_house_recipients'  => $data['in_house_recipients'],
				'out_house_recipients' => $data['out_house_recipients'],
			);
			// since 2.7.0.
			if ( Malware_Report::SLUG !== $slug ) {
				$import['frequency'] = $data['frequency'];
				$import['day_n']     = $data['day_n'];
				$import['day']       = $data['day'];
				$import['time']      = $data['time'];
			}
			foreach ( $import['out_house_recipients'] as $key => $val ) {
				if ( ! filter_var( $val['email'], FILTER_VALIDATE_EMAIL ) ) {
					unset( $import['out_house_recipients'][ $key ] );
				}
			}
			$model->import( $import );
			if ( $model->validate() ) {
				if ( 0 === $model->last_sent ) {
					$model->last_sent = time();
				}
				$model->save();
				$this->service->send_subscription_confirm_email( $model );
				// Track.
				if ( $this->is_tracking_active() ) {
					$track_data = array(
						'Notification type'     => $model->title,
						'Notification schedule' => 'tweak-reminder' === $slug
							? ucfirst( $data['configs']['reminder'] )
							: ucfirst( $data['frequency'] ),
					);
					$this->track_feature( 'def_notification_activated', $track_data );
				}
			}
		}
	}

	/**
	 * Saves the notifications based on the provided data.
	 *
	 * @param  array $data  The data containing the configurations for the notifications.
	 *
	 * @return void
	 * @throws Exception Emits Exception in case of an error.
	 */
	private function save_notifications( $data ) {
		foreach ( $data['configs'] as $datum ) {
			$slug  = $datum['slug'];
			$model = $this->service->find_module_by_slug( $slug );
			if ( ! is_object( $model ) ) {
				continue;
			}
			$import = array(
				// Saving after Bulk-Update must always change the status to Active.
				'status'               => Model_Notification::STATUS_ACTIVE,
				'configs'              => $model->type_casting( $datum ),
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
				if ( 0 === $model->last_sent ) {
					$model->last_sent = time();
				}
				$model->save();
				$this->service->send_subscription_confirm_email( $model );
				// Track.
				if ( $this->is_tracking_active() ) {
					$this->track_feature( 'def_notification_activated', array( 'Notification type' => $model->title ) );
				}
			}
		}
	}

	/**
	 * Bulk activate.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @defender_route
	 * @return Response
	 * @throws Exception Emits Exception in case of an error.
	 */
	public function bulk_activate( Request $request ): Response {
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
	 * Bulk deactivate.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function bulk_deactivate( Request $request ): Response {
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
				array( 'message' => esc_html__( 'You have deactivated the notifications successfully.', 'wpdef' ) ),
				$this->data_frontend()
			)
		);
	}

	/**
	 * Disable a notification module.
	 *
	 * @param  Request $request  The request object .The request object containing the data to disable the module.
	 *
	 * @defender_route
	 * @return Response The response object indicating the success or failure of the operation.
	 */
	public function disable( Request $request ): Response {
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
			return new Response( false, array( 'message' => esc_html__( 'Invalid data.', 'wpdef' ) ) );
		}

		$model->status = Model_Notification::STATUS_DISABLED;
		$model->save();

		return new Response(
			true,
			array_merge(
				$this->data_frontend(),
				array(
					'message' => esc_html__( 'You have deactivated the notification successfully.', 'wpdef' ),
				)
			)
		);
	}

	/**
	 * This is a receiver to process subscribe confirmation from email.
	 */
	public function verify_subscriber() {
		$hash    = HTTP::get( 'hash', '' );
		$slug    = HTTP::get( 'uid', '' );
		$inhouse = HTTP::get( 'inhouse', 0 );
		if ( $inhouse && ! is_user_logged_in() ) {
			// This is in-house, so we need to redirect.
			auth_redirect();
		}
		if ( empty( $hash ) || empty( $slug ) ) {
			wp_die( esc_html__( 'You shall not pass.', 'wpdef' ) );
		}
		$m = $this->service->find_module_by_slug( $slug );
		if ( ! is_object( $m ) ) {
			wp_die( esc_html__( 'You shall not pass.', 'wpdef' ) );
		}
		if ( $inhouse ) {
			$processed = false;
			foreach ( $m->in_house_recipients as &$recipient ) {
				if ( Model_Notification::USER_SUBSCRIBED === $recipient['status'] ) {
					continue;
				}

				$email = $recipient['email'];
				$name  = $recipient['name'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) )
					&& $email === $this->get_current_user_email() ) {
					$recipient['status'] = Model_Notification::USER_SUBSCRIBED;
					$this->service->send_subscribed_email( $email, $m, $name );
					$processed = true;
				}
			}
		} else {
			foreach ( $m->out_house_recipients as &$recipient ) {
				if ( Model_Notification::USER_SUBSCRIBED === $recipient['status'] ) {
					continue;
				}

				$email = $recipient['email'];
				$name  = $recipient['name'];
				if ( hash_equals( $hash, hash( 'sha256', $email . AUTH_SALT ) ) ) {
					$recipient['status'] = Model_Notification::USER_SUBSCRIBED;
					$this->service->send_subscribed_email( $email, $m, $name );
				}
			}
		}
		$m->save();
		if ( $inhouse ) {
			if ( $processed ) {
				wp_safe_redirect(
					add_query_arg(
						array(
							'slug'    => $m->slug,
							'context' => 'subscribed',
						),
						get_edit_profile_url()
					)
				);
			} else {
				wp_safe_redirect( home_url() );
			}
		} else {
			wp_safe_redirect( home_url() );
		}
		exit;
	}

	/**
	 * Enqueues scripts and styles for this page.
	 * Only enqueues assets if the page is active.
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
		wp_enqueue_script( 'def-momentjs', defender_asset_url( '/assets/js/vendor/moment/moment.min.js' ), array(), DEFENDER_VERSION, true );
		wp_enqueue_script( 'def-notification' );
		$this->enqueue_main_assets();
		wp_enqueue_style(
			'def-select2',
			defender_asset_url( '/assets/css/select2.min.css' ),
			array(),
			DEFENDER_VERSION,
			true
		);
	}

	/**
	 * An endpoint for fetching users pool.
	 *
	 * @param  Request $request  The request object .Request data.
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
		$exclude  = $data['exclude'] ?? array();
		$username = $data['search'] ?? '';
		$slug     = $data['module'] ?? null;
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

	/**
	 * Removes settings for all submodules.
	 */
	public function remove_settings() {
		foreach ( $this->service->get_modules_as_objects() as $module ) {
			$module->delete();
		}
	}

	/**
	 * Delete all the data & the cache.
	 */
	public function remove_data() {
	}

	/**
	 * Converts the current object state to an array.
	 *
	 * @return array The array representation of the object.
	 */
	public function to_array(): array {
		return array();
	}

	/**
	 * Provides data for the frontend.
	 *
	 * @return array An array of data for the frontend.
	 */
	public function data_frontend(): array {
		return array(
			'notifications'          => $this->service->get_modules(),
			'inactive_notifications' => $this->service->get_inactive_modules(),
			'active_count'           => $this->service->count_active(),
			'next_run'               => $this->service->get_next_run(),
			'misc'                   => array(
				'days_of_week'      => $this->get_days_of_week(),
				'times_of_day'      => $this->get_times(),
				'timezone_text'     => sprintf(
				/* translators: 1. Timezone. 2. Time. */
					esc_html__(
						'Your timezone is set to %1$s, so your current time is %2$s.',
						'wpdef'
					),
					'<strong>' . wp_timezone_string() . '</strong>',
					'<strong>' . wp_date( 'H:i', time() ) . '</strong>'
				),
				'default_recipient' => $this->get_default_recipient(),
			),
		);
	}

	/**
	 * Imports data into the model.
	 *
	 * @param  array $data  Data to be imported into the model.
	 */
	public function import_data( array $data ) {
	}

	/**
	 * Exports strings.
	 *
	 * @return array An array of strings.
	 */
	public function export_strings(): array {
		$modules = wd_di()->get( self::class )->service->get_modules_as_objects();
		$strings = array();
		foreach ( $modules as $module ) {
			/* translators: %s - module title, %s - module status */
			$string = esc_html__( '%1$s: %2$s', 'wpdef' );
			if ( 'notification' === $module->type ) {
				$string = sprintf(
					$string,
					$module->title,
					Model_Notification::STATUS_ACTIVE === $module->status ? esc_html__(
						'Enabled',
						'wpdef'
					) : esc_html__( 'Disabled', 'wpdef' )
				);
			} else {
				$string = sprintf(
					$string,
					$module->title,
					Model_Notification::STATUS_ACTIVE === $module->status ? $module->to_string() : esc_html__(
						'Disabled',
						'wpdef'
					)
				);
			}
			$strings[] = $string;
		}

		return $strings;
	}

	/**
	 * Resend invite email.
	 *
	 * @param  Request $request  The request object.
	 *
	 * @defender_route
	 * @return Response
	 */
	public function resend_invite_email( Request $request ): Response {
		$data = $request->get_data(
			array(
				'slug'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_textarea_field',
				),
				'email' => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
				'id'    => array(
					'type' => 'integer',
				),
				'name'  => array(
					'type'     => 'string',
					'sanitize' => 'sanitize_text_field',
				),
			)
		);

		$model = $this->service->find_module_by_slug( $data['slug'] );

		if ( ! is_object( $model ) ) {
			return new Response( false, array( 'message' => esc_html__( 'Module not found.', 'wpdef' ) ) );
		}

		$subscriber = array(
			'email' => $data['email'],
			'name'  => $data['name'],
		);

		if ( ! empty( $data['id'] ) ) {
			$subscriber['id'] = $data['id'];
		}
		// Resend invite email now.
		$sent = $this->service->send_email( $subscriber, $model );

		if ( $sent ) {
			return new Response( true, array( 'message' => esc_html__( 'Invitation sent successfully.', 'wpdef' ) ) );
		}

		return new Response(
			false,
			array(
				'message' => esc_html__( 'Sorry! We could not send the invitation, Please try again later.', 'wpdef' ),
			)
		);
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