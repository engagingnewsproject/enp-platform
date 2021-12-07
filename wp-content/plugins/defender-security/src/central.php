<?php

namespace WP_Defender;

use Calotes\Component\Request;
use Calotes\Component\Response;
use Calotes\Helper\HTTP;
use WP_Defender\Traits\IO;
use WP_Defender\Traits\Permission;

/**
 * This class will act as a central manager, every request must go through this.
 * Also, it should manage the data state of every model too.
 *
 * Class Central
 * @package WP_Defender
 */
class Central extends Component {
	use IO, Permission;

	const ACTION = 'wp_defender/v1/hub/';
	/**
	 * This will hold the db data of each module, all data must be getting through this.
	 * @var array
	 */
	protected $states = [];

	/**
	 * This should be constructed only once.
	 */
	public function __construct() {
		add_action( 'wp_ajax_' . self::ACTION, [ &$this, 'routing' ] );
		add_action( 'wp_ajax_nopriv_' . self::ACTION, [ &$this, 'routing' ] );
	}

	/**
	 * This is a global ajax call, receive all the requests and dispatch to the right controller.
	 */
	public function routing() {
		// This is the intention, we will use it to find the data stored in DI.
		$route = HTTP::get( 'route', false );
		$nonce = HTTP::get( '_def_nonce', false );
		if ( empty( $route ) || empty( $nonce ) ) {
			exit;
		}

		$this->check_opcache();

		$route = wp_unslash( $route );

		if (
			is_admin() &&
			! is_user_logged_in() &&
			$this->is_private_access( $route )
		) {
			wp_send_json_error(
				array(
					'message'     => __( 'Your session expired. Please login to continue.', 'wpdef' ),
					'type_notice' => 'session_out',
				)
			);
		}

		// Nonce is not valid.
		if ( ! wp_verify_nonce( $nonce, $route ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Invalid API request.', 'wpdef' ),
				)
			);
		}

		$key = sprintf( 'controller.%s', $route );

		try {
			$package = wd_di()->get( $key );
			list( $class, $method, $is_private ) = $package;
			if ( $is_private && ! $this->check_permission() ) {
				wp_send_json_error( [
					'message' => 'you shall not pass'
				] );
			}
			if ( $is_private ) {
				if ( ! wp_next_scheduled( 'defender_hub_sync' ) ) {
					// Sync with HUB on every request it made, but not on public call.
					wp_schedule_single_event( time(), 'defender_hub_sync' );
				}
			}
			$this->execute_intention( $class, $method );
		} catch ( \Exception $e ) {
			$this->log( $e->getMessage(), 'internal.log' );
		}
	}

	/**
	 * Execute the method, return should be various base on the method.
	 *
	 * @param string $class
	 * @param string $method
	 *
	 * @return Response
	 */
	private function execute_intention( $class, $method ) {
		$object = wd_di()->get( $class );

		if ( is_object( $object ) ) {
			$request = new Request();
			// Because the method is getting params from $_REQUEST directly, we don't need to pass any args, just call.
			// No use reflection method for performance, also this just a simple call.
			// Manipulate the POST as raw data.
			$_POST = $request->get_data();

			return $object->$method( $request );
		} else {
			$this->log( sprintf( 'class not found when executing: %s %s', $class, $method ), 'internal.log' );
		}
	}

	/**
	 * @param string $method     The function to call.
	 * @param string $class      Class name.
	 * @param bool   $is_private Should this expose for non-auth user.
	 *
	 * @return void
	 */
	public function add_route( $method, $class, $is_private = true ) {
		// This will be passed into frontend for the query later.
		$intention = hash( 'md5', sprintf( '%s.%s.%d', $class, $method, get_current_user_id() ) );
		if ( defined( 'DEFENDER_DEBUG' ) && constant( 'DEFENDER_DEBUG' ) === true ) {
			$intention = sprintf( '%s.%s.%d', $class, $method, get_current_user_id() );
		}
		wd_di()->set( sprintf( 'controller.%s', $intention ), [ $class, $method, $is_private ] );
		wd_di()->set( sprintf( 'route.%s', $intention ), $intention );
		wd_di()->set( sprintf( 'nonce.%s', $intention ), wp_create_nonce( $intention ) );
	}

	/**
	 * @param string $method
	 * @param string $class
	 *
	 * @return mixed
	 */
	public function get_route( $method, $class ) {
		$intention = hash( 'md5', sprintf( '%s.%s.%d', $class, $method, get_current_user_id() ) );
		if ( defined( 'DEFENDER_DEBUG' ) && constant( 'DEFENDER_DEBUG' ) === true ) {
			$intention = sprintf( '%s.%s.%d', $class, $method, get_current_user_id() );
		}
		try {
			return wd_di()->get( sprintf( 'route.%s', $intention ) );
		} catch ( \Exception $e ) {
			$this->log( $e->getMessage(), 'internal.log' );
		}
	}

	/**
	 * @param string $method
	 * @param string $class
	 *
	 * @return mixed
	 */
	public function get_nonce( $method, $class ) {
		$intention = hash( 'md5', sprintf( '%s.%s.%d', $class, $method, get_current_user_id() ) );
		if ( defined( 'DEFENDER_DEBUG' ) && constant( 'DEFENDER_DEBUG' ) === true ) {
			$intention = sprintf( '%s.%s.%d', $class, $method, get_current_user_id() );
		}
		try {
			return wd_di()->get( sprintf( 'nonce.%s', $intention ) );
		} catch ( \Exception $e ) {
			$this->log( $e->getMessage(), 'internal.log' );
		}
	}

	/**
	 * Check OPcache is enabled or not.
	 *
	 * @return void
	 */
	private function check_opcache() {
		if ( $this->is_opcache_save_comments_disabled() ) {
			wp_send_json_error(
				array(
					'message' => sprintf( __( '%s is disabled. Please contact your hosting provider to enable it.', 'wpdef' ), '<strong>OPcache Save Comments</strong>' ),
				)
			);
		}
	}

	/**
	 * Check OPcache is enabled or not.
	 *
	 * @return bool
	 */
	public function is_opcache_save_comments_disabled() {
		// If OPcache is disabled.
		if ( ini_get( 'opcache.enable' ) !== '1' ) {
			return false;
		}

		// If OPcache is enabled and save comments disabled.
		if ( ini_get( 'opcache.save_comments' ) !== '1' ) {
			return true;
		}

		// Any other case.
		return false;
	}

	/**
	 * Verify is ajax call is private.
	 *
	 * Here private stands for only authenticated user can do ajax call.
	 *
	 * @param string $route Route md5 hash.
	 *
	 * @return bool Return true if the request is private else false.
	 */
	private function is_private_access( $route ) {
		$key = sprintf( 'controller.%s', $route );

		try {
			$package = wd_di()->get( $key );
			return isset( $package[2] ) && $package[2];
		} catch ( \Exception $e ) {
			$this->log( $e->getMessage(), 'internal.log' );
		}

		return false;
	}
}
