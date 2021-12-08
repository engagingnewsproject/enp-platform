<?php

namespace WP_Defender\Component\Security_Tweaks;

use Calotes\Base\Component;

/**
 * Class Disable_XML_RPC
 * @package WP_Defender\Component\Security_Tweaks
 */
class Disable_XML_RPC extends Component {
	public $slug = 'disable-xml-rpc';
	public $resolved = false;

	/**
	 * Check whether the issue has been resolved or not.
	 *
	 * @return bool
	 */
	public function check() {
		return $this->resolved;
	}

	/**
	 * Here is the code for processing, if the return is true, we add it to resolve list, WP_Error if any error.
	 *
	 * @return bool
	 */
	public function process() {
		return true;
	}

	/**
	 * This is for un-do stuff that has be done in @process.
	 *
	 * @return bool
	 */
	public function revert() {
		return true;
	}

	/**
	 * Shield up.
	 *
	 * @return void
	 */
	public function shield_up() {
		$this->resolved = true;

		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', [ $this, 'block_xmlrpc_attacks' ] );
	}

	/**
	 * Block XML-RFC attacks.
	 *
	 * @param array $methods
	 *
	 * @return array
	 */
	public function block_xmlrpc_attacks( $methods ) {
		unset( $methods['pingback.ping'] );
		unset( $methods['pingback.extensions.getPingbacks'] );

		return $methods;
	}

	/**
	 * Return a summary data of this tweak.
	 *
	 * @return array
	 */
	public function to_array() {
		return [
			'slug'             => $this->slug,
			'title'            => __( 'Disable XML-RPC', 'wpdef' ),
			'errorReason'      => __( 'XML-RPC is currently enabled.', 'wpdef' ),
			'successReason'    => __( 'XML-RPC is disabled, great job!', 'wpdef' ),
			'misc'             => [],
			'bulk_description' => __( 'In the past, there were security concerns with XML-RPC so we recommend making sure this feature is fully disabled if you don’t need it active. We will disable XML-RPC for you.', 'wpdef' ),
			'bulk_title'       => 'XML-RPC'
		];
	}
}