<?php
/**
 * Handles user-related audit events.
 *
 * @package WP_Defender\Component\Audit
 */

namespace WP_Defender\Component\Audit;

use WP_User;
use WP_Defender\Traits\User;
use WP_Defender\Model\Audit_Log;

/**
 * Handle user-related audit events such as login, logout, registration, and profile updates.
 */
class Users_Audit extends Audit_Event {

	use User;

	public const ACTION_LOGIN = 'login', ACTION_LOGOUT = 'logout', ACTION_REGISTERED = 'registered',
		ACTION_LOST_PASS      = 'lost_password', ACTION_RESET_PASS = 'reset_password';

	public const CONTEXT_SESSION = 'session', CONTEXT_USERS = 'users', CONTEXT_PROFILE = 'profile';

	/**
	 * Returns an array of hooks associated with various user actions to capture and log them.
	 *
	 * @return array
	 */
	public function get_hooks(): array {

		return array(
			'wp_login_failed'       => array(
				'args'        => array( 'username' ),
				'text'        => sprintf(
				/* translators: 1: Blog name, 2: Username */
					esc_html__( '%1$s User login fail. Username: %2$s', 'wpdef' ),
					'{{blog_name}}',
					'{{username}}'
				),
				'event_type'  => Audit_Log::EVENT_TYPE_USER,
				'context'     => self::CONTEXT_SESSION,
				'action_type' => self::ACTION_LOGIN,
			),
			'wp_login'              => array(
				'args'        => array( 'userlogin', 'user' ),
				'text'        => sprintf(
				/* translators: 1: Blog name, 2: Username */
					esc_html__( '%1$s User login success: %2$s', 'wpdef' ),
					'{{blog_name}}',
					'{{userlogin}}'
				),
				'event_type'  => Audit_Log::EVENT_TYPE_USER,
				'context'     => self::CONTEXT_SESSION,
				'action_type' => self::ACTION_LOGIN,
			),
			'wpmu_2fa_login'        => array(
				'args'         => array( 'user_id', '2fa_slug' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: 2fa method slug, 3: Username. */
					esc_html__( '%1$s 2fa with %2$s method login success for user: %3$s', 'wpdef' ),
					'{{blog_name}}',
					'{{2fa_slug}}',
					'{{username}}'
				),
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'context'      => self::CONTEXT_SESSION,
				'action_type'  => self::ACTION_LOGIN,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}',
						),
						'result_property' => 'user_login',
					),
				),
			),
			'wp_logout'             => array(
				'args'         => array( 'user_id' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Username */
					esc_html__( '%1$s User logout success: %2$s', 'wpdef' ),
					'{{blog_name}}',
					'{{username}}'
				),
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'action_type'  => self::ACTION_LOGOUT,
				'context'      => self::CONTEXT_SESSION,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}',
						),
						'result_property' => 'user_login',
					),
				),
			),
			'user_register'         => array(
				'args'         => array( 'user_id' ),
				'text'         => is_admin()
					? sprintf(
					/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Username, 4: User role */
						esc_html__( '%1$s %2$s added a new user: Username: %3$s, Role: %4$s', 'wpdef' ),
						'{{blog_name}}',
						'{{wp_user}}',
						'{{username}}',
						'{{user_role}}'
					)
					: sprintf(
					/* translators: 1: Blog name, 2: Username, 3: User role */
						esc_html__( '%1$s A new user registered: Username: %2$s, Role: %3$s', 'wpdef' ),
						'{{blog_name}}',
						'{{username}}',
						'{{user_role}}'
					),
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'context'      => self::CONTEXT_USERS,
				'action_type'  => self::ACTION_REGISTERED,
				'program_args' => array(
					'username'  => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}',
						),
						'result_property' => 'user_login',
					),
					'user_role' => array(
						'callable' => array( self::class, 'get_user_role' ),
						'params'   => array(
							'{{user_id}}',
						),
					),
				),
			),
			'delete_user'           => array(
				'args'         => array( 'user_id' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: User ID, 4: Username */
					esc_html__( '%1$s %2$s deleted a user: ID: %3$s, username: %4$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{user_id}}',
					'{{username}}'
				),
				'context'      => self::CONTEXT_USERS,
				'action_type'  => self::ACTION_DELETED,
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}',
						),
						'result_property' => 'user_login',
					),
				),
			),
			'remove_user_from_blog' => array(
				'args'        => array( 'user_id', 'blog_id' ),
				'context'     => self::CONTEXT_USERS,
				'action_type' => self::ACTION_DELETED,
				'event_type'  => Audit_Log::EVENT_TYPE_USER,
				'callback'    => array( self::class, 'remove_user_from_blog_callback' ),
			),
			'wpmu_delete_user'      => array(
				'args'         => array( 'user_id' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: User ID, 4: Username */
					esc_html__( '%1$s %2$s deleted a user: ID: %3$s, username: %4$s', 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{user_id}}',
					'{{username}}'
				),
				'context'      => self::CONTEXT_USERS,
				'action_type'  => self::ACTION_DELETED,
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}',
						),
						'result_property' => 'user_login',
					),
				),
			),
			'profile_update'        => array(
				'args'        => array( 'user_id', 'old_user_data' ),
				'action_type' => self::ACTION_UPDATED,
				'event_type'  => Audit_Log::EVENT_TYPE_USER,
				'context'     => self::CONTEXT_PROFILE,
				'callback'    => array( self::class, 'profile_update_callback' ),
			),
			'retrieve_password'     => array(
				'args'         => array( 'username' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Username */
					esc_html__( '%1$s Password requested to reset for user: %2$s', 'wpdef' ),
					'{{blog_name}}',
					'{{username}}'
				),
				'action_type'  => self::ACTION_LOST_PASS,
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'context'      => self::CONTEXT_PROFILE,
				'program_args' => array(
					'user' => array(
						'callable' => 'get_user_by',
						'params'   => array(
							'login',
							'{{username}}',
						),
					),
				),
			),
			'after_password_reset'  => array(
				'args'        => array( 'user' ),
				'text'        => sprintf(
				/* translators: 1: Blog name, 2: Username. */
					esc_html__( '%1$s Password reset for user: %2$s', 'wpdef' ),
					'{{blog_name}}',
					'{{user_login}}'
				),
				'event_type'  => Audit_Log::EVENT_TYPE_USER,
				'action_type' => self::ACTION_RESET_PASS,
				'context'     => self::CONTEXT_PROFILE,
				'custom_args' => array(
					'user_login' => '{{user->user_login}}',
				),
			),
			'set_user_role'         => array(
				'args'         => array( 'user_ID', 'new_role', 'old_role' ),
				'text'         => sprintf(
				/* translators: 1: Blog name, 2: Source of action. For e.g. Hub or a logged-in user, 3: Username, 4: Old user role, 5: New user role */
					esc_html__( "%1\$s %2\$s changed user %3\$s's role from %4\$s to %5\$s", 'wpdef' ),
					'{{blog_name}}',
					'{{wp_user}}',
					'{{username}}',
					'{{from_role}}',
					'{{new_role}}'
				),
				'action_type'  => self::ACTION_UPDATED,
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'context'      => self::CONTEXT_PROFILE,
				'custom_args'  => array(
					'from_role' => '{{old_role->0}}',
				),
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_ID}}',
						),
						'result_property' => 'user_login',
					),
				),
				'false_when'   => array(
					array(
						'{{old_role}}',
						array(),
						'==',
					),
				),
			),
			'wpdef_session_lock'    => array(
				'args'         => array( 'user_id', 'session_lock_type' ),
				'text'         => sprintf(
					/* translators: 1: Blog name, 2: Username, 3: Session lock type. */
					esc_html__( '%1$s User session ended for %2$s due to a change in %3$s', 'wpdef' ),
					'{{blog_name}}',
					'{{username}}',
					'{{session_lock_type}}'
				),
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'context'      => self::CONTEXT_SESSION,
				'action_type'  => self::ACTION_LOGIN,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}',
						),
						'result_property' => 'user_login',
					),
				),
			),
			'wpdef_session_timeout' => array(
				'args'         => array( 'user_id' ),
				'text'         => sprintf(
					/* translators: 1: Blog name, 2: Username. */
					esc_html__( '%1$s User session ended for %2$s due to an idle session', 'wpdef' ),
					'{{blog_name}}',
					'{{username}}'
				),
				'event_type'   => Audit_Log::EVENT_TYPE_USER,
				'context'      => self::CONTEXT_SESSION,
				'action_type'  => self::ACTION_LOGIN,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}',
						),
						'result_property' => 'user_login',
					),
				),
			),
		);
	}

	/**
	 * Log when user is removed from a blog.
	 *
	 * @return bool|array
	 */
	public function remove_user_from_blog_callback() {
		if ( self::is_create_user_action() ) {
			return false;
		}

		$args                 = func_get_args();
		$user_id              = $args[1]['user_id'];
		$blog_id              = $args[1]['blog_id'];
		$user                 = get_user_by( 'id', $user_id );
		$username             = $user->user_login ?? '';
		$current_user_display = $this->get_user_display( get_current_user_id() );
		$blog_name            = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';

		return array(
			sprintf(
			/* translators: 1: Blog name, 2: User's display name, 3: User ID, 4: Username, 5: Blog ID */
				esc_html__( '%1$s %2$s removed a user: ID: %3$s, username: %4$s from blog %5$s', 'wpdef' ),
				$blog_name,
				$current_user_display,
				$user_id,
				$username,
				$blog_id
			),
			self::ACTION_DELETED,
		);
	}

	/**
	 * Callback for logging when a user's profile is updated. It distinguishes between self-updates and updates made by
	 * other users.
	 *
	 * @return bool|array
	 */
	public function profile_update_callback() {
		if ( self::is_create_user_action() ) {
			return false;
		}

		$args            = func_get_args();
		$user_id         = $args[1]['user_id'];
		$current_user    = get_user_by( 'id', $user_id );
		$blog_name       = is_multisite() ? '[' . get_bloginfo( 'name' ) . ']' : '';
		$current_user_id = get_current_user_id();

		if ( $current_user_id === $user_id ) {

			return array(
				sprintf(
				/* translators: 1: Blog name, 2: User's nicename */
					esc_html__( '%1$s User %2$s updated his/her profile', 'wpdef' ),
					$blog_name,
					$current_user->user_nicename
				),
				self::ACTION_UPDATED,
			);
		} elseif ( 0 !== $current_user_id ) {

			return array(
				sprintf(
				/* translators: 1: Blog name, 2: User's display name, 3: User's nicename */
					esc_html__( "%1\$s %2\$s updated user %3\$s's profile information", 'wpdef' ),
					$blog_name,
					$this->get_user_display( $current_user_id ),
					$current_user->user_nicename
				),
				self::ACTION_UPDATED,
			);
		}
	}

	/**
	 * Provides a dictionary for translating action constants into human-readable strings.
	 *
	 * @return array
	 */
	public function dictionary(): array {
		return array(
			self::ACTION_LOST_PASS  => esc_html__( 'lost password', 'wpdef' ),
			self::ACTION_REGISTERED => esc_html__( 'registered', 'wpdef' ),
			self::ACTION_LOGIN      => esc_html__( 'login', 'wpdef' ),
			self::ACTION_LOGOUT     => esc_html__( 'logout', 'wpdef' ),
			self::ACTION_RESET_PASS => esc_html__( 'password reset', 'wpdef' ),
		);
	}

	/**
	 * Static method to retrieve the role of a user by their ID
	 *
	 * @param  int $user_id  The ID of the user whose role is to be fetched.
	 *
	 * @return string
	 */
	public static function get_user_role( $user_id ): string {
		$user = get_user_by( 'id', $user_id );
		if ( $user instanceof WP_User ) {
			$_this = new self();

			return $_this->get_first_user_role( $user );
		} else {
			return '';
		}
	}

	/**
	 * Check if it is a create new user request.
	 *
	 * @since 2.8.0
	 * @retun bool
	 */
	public static function is_create_user_action(): bool {
		$action = defender_get_data_from_request( 'action', 'p' );
		if ( 'createuser' === $action ) {
			return true;
		}

		return false;
	}
}