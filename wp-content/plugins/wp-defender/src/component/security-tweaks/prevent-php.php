<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;
use WP_Defender\Component\Security_Tweaks\Servers\Server;

/**
 * Class Prevent_PHP
 * @package WP_Defender\Component\Security_Tweaks
 */
class Prevent_PHP extends Component {
	public $slug = 'prevent-php-executed';

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		return Server::create( Server::get_current_server() )->from( $this->slug )->check();
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 * @param string      $current_server
	 *
	 * @return bool|\WP_Error
	 */
	public function process( $current_server ) {
		return Server::create( $current_server )->from( $this->slug )->process();
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 * @param string|null $current_server
	 *
	 * @return bool|\WP_Error
	 */
	public function revert( $current_server = null ) {
		if ( is_null( $current_server ) ) {
			$current_server = Server::get_current_server();
		}
		return Server::create( $current_server )->from( $this->slug )->revert();
	}

	/**
	 * Shield up.
	 *
	 * @return bool
	 */
	public function shield_up() {
		return true;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Prevent PHP Execution', 'wpdef' ),
			'errorReason'      => __( 'PHP execution is currently allowed in all directories.', 'wpdef' ),
			'successReason'    => __( 'You\'ve disabled PHP execution, good stuff.', 'wpdef' ),
			'misc'             => [
				'active_server'  => Server::get_current_server(),
				'apache_rules'   => Server::create( 'apache' )->from( $this->slug )->get_rules_for_instruction(),
				'nginx_rules'    => Server::create( 'nginx' )->from( $this->slug )->get_rules(),
				'wp_content_dir' => WP_CONTENT_DIR
			],
			'bulk_description' => __( 'By default, a plugin/theme vulnerability could allow a PHP file to get uploaded into your site\'s directories and in turn execute harmful scripts that can wreak havoc on your website. We will disable PHP execution for you.', 'wpdef' ),
			'bulk_title'       => __( 'PHP Execution', 'wpdef' )
		];
	}
}