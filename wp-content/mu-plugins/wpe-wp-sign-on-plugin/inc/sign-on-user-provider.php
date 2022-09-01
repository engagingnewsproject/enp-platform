<?php

namespace wpengine\sign_on_plugin;

require_once __DIR__ . '/security-checks.php';
\wpengine\sign_on_plugin\check_security();

require_once __DIR__ . '/logger.php';


class SignOnUserProvider {

	private $user_request_id_helper;

	const WPE_USER_CREATED_TIME = 'WPE_USER_CREATED_TIME';
	const WPE_LAST_LOGIN_TIME   = 'WPE_LAST_LOGIN_TIME';
	const DEFAULT_USER_ROLE     = 'administrator';
	const WPE_ADMIN_USER_EMAIL  = 'bitbucket@wpengine.com';

	public function get_wp_user( $user_email ) {
		$user = get_user_by( 'email', $user_email );

		$user = $user ? $user : new \WP_User();

		return $user;
	}

	public function __construct( $user_request_id_helper ) {
		$this->user_request_id_helper = $user_request_id_helper;
	}

	public function get_or_create_wp_user( $user_email, $first_name, $last_name, $role ) {
		$user = $this->get_wp_user( $user_email );

		if ( $this->is_new_user( $user ) ) {
			if ( self::WPE_ADMIN_USER_EMAIL === $user_email ) {
				throw new ImpersonatedUserException( 'The wpengine user was not found in the WordPress database during an attempted impersonation' );
			}
			$user = $this->create_new_wp_user( $user_email, $first_name, $last_name, $role );
		}

		return $user;
	}

	public function login_user( $user, $start_time, $request_id ) {
		wp_clear_auth_cookie();
		wp_set_auth_cookie( $user->ID, true );
		wp_set_current_user( $user->ID );

		do_action( 'wp_login', $user->user_login, $user );

		$this->update_last_login_time_to_meta_data( $user );
		$this->user_request_id_helper->update_request_id_user_meta( $user->user_email, $request_id );

		$email        = $user->user_email;
		$roles        = implode( ',', $user->roles );
		$install_name = PWP_NAME;
		$elapsed_time = $this->calc_elapsed_time_ms( $start_time );
		Logger::log( 'login_user', "email: { $email }, roles: { $roles }, install name: { $install_name }, response time(ms): { $elapsed_time }", $email, PWP_NAME );
	}

	public function rollback_user_creation( $user_email ) {
		$user = $this->get_wp_user( $user_email );
		if ( 0 !== $user->ID ) {
			wp_delete_user( $user->ID );
		}
	}

	public function validate_role( $role ) {
		return array_key_exists( $role, $this->get_wp_roles() );
	}

	public function user_email_matches_current_user( $user_email ) {
		$user = $this->determine_current_user();

		return $user && $user->exists() && strcasecmp( $user->data->user_email, $user_email ) === 0;
	}

	protected function add_user_created_time_to_meta_data( $user_id ) {
		return add_user_meta( $user_id, self::WPE_USER_CREATED_TIME, time() );
	}

	private function determine_current_user() {
		$user_id = apply_filters( 'determine_current_user', false );
		$user    = get_user_by( 'id', $user_id );
		return $user;
	}

	private function update_last_login_time_to_meta_data( $user ) {
		return update_user_meta( $user->ID, self::WPE_LAST_LOGIN_TIME, time() );
	}

	private function create_new_wp_user( $user_email, $first_name, $last_name, $role ) {
		$user_data   = array(
			'user_email' => $user_email,
			'user_login' => $user_email,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			// WordPress will generate a random password when user_pass is set to null.
			'user_pass'  => null,
			'role'       => $role,
		);
		$new_user_id = wp_insert_user( $user_data );

		if ( is_wp_error( $new_user_id ) ) {
			throw new UserCreationException( $new_user_id->get_error_message() );
		}

		if ( ! $this->add_user_created_time_to_meta_data( $new_user_id ) ) {
			$user_created = self::WPE_USER_CREATED_TIME;
			throw new UserMetaAdditionException( "The meta key {$user_created} could not be added to users ({$user_email}) meta data " );
		}

		$user = new \WP_User( $new_user_id );

		return $user;
	}

	private function is_new_user( $user ) {
		return 0 === $user->ID;
	}

	private function get_wp_roles() {
		global $wp_roles;

		$roles = $wp_roles->roles;
		return $roles;
	}

	private function calc_elapsed_time_ms( $time_start ) {
		$time_stop = round( microtime( true ) * 1000 );
		$time      = $time_stop - $time_start;

		return $time;
	}
}
