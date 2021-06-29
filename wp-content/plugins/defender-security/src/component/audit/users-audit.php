<?php
/**
 * Author: Hoang Ngo
 */


namespace WP_Defender\Component\Audit;

use WP_Defender\Traits\User;

class Users_Audit extends Audit_Event {
	use User;

	const ACTION_LOGIN = 'login', ACTION_LOGOUT = 'logout', ACTION_REGISTERED = 'registered', ACTION_LOST_PASS = 'lost_password',
		ACTION_RESET_PASS = 'reset_password';

	const CONTEXT_SESSION = 'session', CONTEXT_USERS = 'users', CONTEXT_PROFILE = 'profile';
	private $type = 'user';

	public function get_hooks() {
		return array(
			'wp_login_failed'       => array(
				'args'        => array( 'username' ),
				'text'        => sprintf( esc_html__( "User login fail. Username: %s", 'wpdef' ), '{{username}}' ),
				'event_type'  => $this->type,
				'context'     => self::CONTEXT_SESSION,
				'action_type' => self::ACTION_LOGIN,
			),
			'wp_login'              => array(
				'args'        => array( 'userlogin', 'user' ),
				'text'        => sprintf( esc_html__( "User login success: %s", 'wpdef' ), '{{userlogin}}' ),
				'event_type'  => $this->type,
				'context'     => self::CONTEXT_SESSION,
				'action_type' => self::ACTION_LOGIN,
			),
			'wp_logout'             => array(
				'args'        => array(),
				'text'        => sprintf( esc_html__( "User logout success: %s", 'wpdef' ), '{{username}}' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_LOGOUT,
				'context'     => self::CONTEXT_SESSION,
				'custom_args' => array(
					//in this state, current user should be the one who log out
					'username' => $this->get_user_display( get_current_user_id() )
				)
			),
			'user_register'         => array(
				'args'         => array( 'user_id' ),
				'text'         => is_admin() ? sprintf( esc_html__( "%s added a new user: Username: %s, Role: %s", 'wpdef' ), '{{wp_user}}', '{{username}}', '{{user_role}}' )
					: sprintf( esc_html__( "A new user registered: Username: %s, Role: %s", 'wpdef' ), '{{username}}', '{{user_role}}' ),
				'event_type'   => $this->type,
				'context'      => self::CONTEXT_USERS,
				'action_type'  => self::ACTION_REGISTERED,
				'program_args' => array(
					'username'  => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}'
						),
						'result_property' => 'user_login'
					),
					'user_role' => array(
						'callable' => array( self::class, 'get_user_role' ),
						'params'   => array(
							'{{user_id}}'
						),
					)
				)
			),
			'delete_user'           => array(
				'args'         => array( 'user_id' ),
				'text'         => sprintf( esc_html__( "%s deleted a user: ID: %s, username: %s", 'wpdef' ), '{{wp_user}}', '{{user_id}}', '{{username}}' ),
				'context'      => self::CONTEXT_USERS,
				'action_type'  => self::ACTION_DELETED,
				'event_type'   => $this->type,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}'
						),
						'result_property' => 'user_login'
					)
				)
			),
			'remove_user_from_blog' => array(
				'args'         => array( 'user_id', 'blog_id' ),
				'text'         => sprintf( esc_html__( "%s removed a user: ID: %s, username: %s from blog %s", 'wpdef' ), '{{wp_user}}', '{{user_id}}', '{{username}}', '{{blog_id}}' ),
				'context'      => self::CONTEXT_USERS,
				'action_type'  => self::ACTION_DELETED,
				'event_type'   => $this->type,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}'
						),
						'result_property' => 'user_login'
					)
				)
			),
			'wpmu_delete_user'      => array(
				'args'         => array( 'user_id' ),
				'text'         => sprintf( esc_html__( "%s deleted a user: ID: %s, username: %s", 'wpdef' ), '{{wp_user}}', '{{user_id}}', '{{username}}' ),
				'context'      => self::CONTEXT_USERS,
				'action_type'  => self::ACTION_DELETED,
				'event_type'   => $this->type,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_id}}'
						),
						'result_property' => 'user_login'
					)
				)
			),
			'profile_update'        => array(
				'args'        => array( 'user_id', 'old_user_data' ),
				'action_type' => self::ACTION_UPDATED,
				'event_type'  => $this->type,
				'context'     => self::CONTEXT_PROFILE,
				'callback'    => array( self::class, 'profile_update_callback' ),
			),
			'retrieve_password'     => array(
				'args'        => array( 'username' ),
				'text'        => sprintf( esc_html__( "Password requested to reset for user: %s", 'wpdef' ), '{{username}}' ),
				'action_type' => self::ACTION_LOST_PASS,
				'event_type'  => $this->type,
				'context'     => self::CONTEXT_PROFILE,
			),
			'after_password_reset'  => array(
				'args'        => array( 'user' ),
				'text'        => sprintf( esc_html__( "Password reset for user: %s", 'wpdef' ), '{{user_login}}' ),
				'event_type'  => $this->type,
				'action_type' => self::ACTION_RESET_PASS,
				'context'     => self::CONTEXT_PROFILE,
				'custom_args' => array(
					'user_login' => '{{user->user_login}}'
				)
			),
			'set_user_role'         => array(
				'args'         => array( 'user_ID', 'new_role', 'old_role' ),
				'text'         => sprintf( __( "%s changed user %s's role from %s to %s", 'wpdef' ), '{{wp_user}}', '{{username}}', '{{from_role}}', '{{new_role}}' ),
				'action_type'  => self::ACTION_UPDATED,
				'event_type'   => $this->type,
				'context'      => self::CONTEXT_PROFILE,
				'custom_args'  => array(
					'from_role' => '{{old_role->0}}',
				),
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{user_ID}}'
						),
						'result_property' => 'user_login'
					),
				),
				'false_when'   => array(
					array(
						'{{old_role}}',
						array(),
						'=='
					),
				),
			),
			/*'updated_user_meta1'    => array(
				'args'         => array( 'meta_id', 'object_id', 'meta_key', 'meta_value' ),
				'text'         => sprintf( esc_html__( "%s changed user %s meta %s", 'wpdef' ), '{{wp_user}}', '{{username}}', '{{meta_key}}' ),
				'action_type'  => self::ACTION_UPDATED,
				'context'      => self::CONTEXT_PROFILE,
				'program_args' => array(
					'username' => array(
						'callable'        => 'get_user_by',
						'params'          => array(
							'id',
							'{{object_id}}'
						),
						'result_property' => 'user_login'
					)
				),
				'event_type'   => $this->type,
			)*/
		);
	}


	public function profile_update_callback() {
		$args         = func_get_args();
		$user_id      = $args[1]['user_id'];
		$current_user = get_user_by( 'id', $user_id );

		if ( get_current_user_id() === $user_id ) {
			return array(
				sprintf( esc_html__( "User %s updated his/her profile", 'wpdef' ), $current_user->user_nicename ),
				self::ACTION_UPDATED
			);
		} else {
			return array(
				sprintf( __( "%s updated user %s's profile information", 'wpdef' ), $this->get_user_display( get_current_user_id() ), $current_user->user_nicename ),
				self::ACTION_UPDATED
			);
		}
	}

	public function dictionary() {
		return array(
			self::ACTION_LOST_PASS  => esc_html__( "lost password", 'wpdef' ),
			self::ACTION_REGISTERED => esc_html__( "registered", 'wpdef' ),
			self::ACTION_LOGIN      => esc_html__( "login", 'wpdef' ),
			self::ACTION_LOGOUT     => esc_html__( "logout", 'wpdef' ),
			self::ACTION_RESET_PASS => esc_html__( "password reset", 'wpdef' ),
		);
	}

	public static function get_user_role( $user_id ) {
		$user = get_user_by( 'id', $user_id );

		return ucfirst( $user->roles[0] );
	}
}