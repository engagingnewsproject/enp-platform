<?php

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
	abstract public function is_available_for_user( $user );

	abstract public function authentication_form();

	/**
	 * @param WP_User $user
	 */
	abstract public function validate_authentication( $user );

	/**
	 * Safe way to get cached model.
	 *
	 * @return Two_Fa_Model
	 */
	protected function get_model() {
		if ( is_object( $this->model ) ) {
			return $this->model;
		}

		return $this->model = wd_di()->get( Two_Fa_Model::class );
	}

	protected function get_controller() {
		return wd_di()->get( \WP_Defender\Controller\Two_Factor::class );
	}

	protected function get_component() {
		return wd_di()->get( \WP_Defender\Component\Two_Fa::class );
	}

	protected function get_url( $action ) {
		$controller = $this->get_controller();
		$routes     = $controller->dump_routes_and_nonces();

		return admin_url( 'admin-ajax.php' ) . sprintf(
				'?action=%s&route=%s&_def_nonce=%s',
				defender_base_action(),
				$controller->check_route( $routes['routes'][ $action ] ),
				$routes['nonces'][ $action ]
			);
	}
}
