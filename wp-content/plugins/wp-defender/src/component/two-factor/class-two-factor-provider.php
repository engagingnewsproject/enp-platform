<?php
/**
 * Abstract class for defining Two-Factor Authentication providers.
 *
 * @package WP_Defender\Component\Two_Factor
 */

namespace WP_Defender\Component\Two_Factor;

use WP_User;
use Calotes\Base\Component;
use WP_Defender\Controller\Two_Factor;
use WP_Defender\Component\Two_Fa as Two_Fa_Component;
use WP_Defender\Model\Setting\Two_Fa as Two_Fa_Model;

/**
 * Abstract class for defining two-factor authentication.
 */
abstract class Two_Factor_Provider extends Component {

	/**
	 * Unique identifier for the two-factor provider.
	 *
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
	 * Retrieves the label of the two-factor provider.
	 *
	 * @return string
	 */
	abstract public function get_label();

	/**
	 * Retrieves the label used during the login process.
	 *
	 * @return string
	 */
	abstract public function get_login_label();

	/**
	 * Retrieves the label for the user interface.
	 *
	 * @return string
	 * @since 2.8.1
	 */
	abstract public function get_user_label();

	/**
	 * Retrieves the description of the two-factor provider.
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Checks if the provider is available for a specific user.
	 *
	 * @param  WP_User $user  The user object to check availability against.
	 *
	 * @return boolean True if available, false otherwise.
	 */
	abstract public function is_available_for_user( WP_User $user );

	/**
	 * Outputs the form needed for authentication with this provider.
	 */
	abstract public function authentication_form();

	/**
	 * Validates the authentication input from the user.
	 *
	 * @param  WP_User $user  The user object that is being authenticated.
	 */
	abstract public function validate_authentication( WP_User $user );

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

	/**
	 * Retrieves the controller instance for two-factor authentication.
	 *
	 * @return Two_Factor The controller instance.
	 */
	protected function get_controller(): Two_Factor {
		return wd_di()->get( Two_Factor::class );
	}

	/**
	 * Retrieves the component instance for two-factor authentication.
	 *
	 * @return Two_Fa_Component The component instance.
	 */
	protected function get_component(): Two_Fa_Component {
		return wd_di()->get( Two_Fa_Component::class );
	}

	/**
	 * Constructs a URL for a specific action related to two-factor authentication.
	 *
	 * @param  string $action  The action to construct the URL for.
	 *
	 * @return string The constructed URL.
	 */
	protected function get_url( $action ): string {
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