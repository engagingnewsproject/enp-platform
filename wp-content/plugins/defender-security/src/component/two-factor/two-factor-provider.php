<?php
declare( strict_types = 1 );

namespace WP_Defender\Component\Two_Factor;

use Calotes\Base\Component;
use WP_Defender\Model\Setting\Two_Fa as Two_Fa_Model;

abstract class Two_Factor_Provider extends Component {

	/**
	 * @var string
	 */
	protected static $slug;

	/**
	 * Use for cache.
	 *
	 * @var Two_Fa_Model
	 */
	public $model;

	/**
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * @return string
	 */
	abstract public function get_login_label();

	/**
	 * @since 2.8.1
	 * @return string
	 */
	abstract public function get_user_label();

	/**
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * @param WP_User $user WP_User object of the logged-in user.
	 *
	 * @return boolean
	 */
	abstract public function is_available_for_user( \WP_User $user );

	abstract public function authentication_form();

	/**
	 * @param WP_User $user
	 */
	abstract public function validate_authentication( \WP_User $user );

	/**
	 * Safe way to get cached model.
	 *
	 * @return Two_Fa_Model
	 */
	protected function get_model(): Two_Fa_Model {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		$this->model = wd_di()->get( Two_Fa_Model::class );

		return $this->model;
	}

	protected function get_controller(): \WP_Defender\Controller\Two_Factor {
		return wd_di()->get( \WP_Defender\Controller\Two_Factor::class );
	}

	protected function get_component(): \WP_Defender\Component\Two_Fa {
		return wd_di()->get( \WP_Defender\Component\Two_Fa::class );
	}

	protected function get_url( $action ): string {
		$controller = $this->get_controller();
		$routes = $controller->dump_routes_and_nonces();

		return admin_url( 'admin-ajax.php' ) . sprintf(
			'?action=%s&route=%s&_def_nonce=%s',
			defender_base_action(),
			$controller->check_route( $routes['routes'][ $action ] ),
			$routes['nonces'][ $action ]
		);
	}
}
