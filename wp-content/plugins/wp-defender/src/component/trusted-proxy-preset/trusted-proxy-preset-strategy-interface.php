<?php
/**
 * The interface for trusted proxy preset class.
 *
 * @package WP_Defender\Component\Trusted_Proxy_Preset
 */

namespace WP_Defender\Component\Trusted_Proxy_Preset;

interface Trusted_Proxy_Preset_Strategy_Interface {

	/**
	 * Retrieve a list of trusted proxy IP addresses.
	 *
	 * @return array An array of IP addresses.
	 */
	public function get_ips(): array;

	/**
	 * Update the list of trusted proxy IP addresses.
	 * This method should handle the logic to update the current list of IPs based on the implementation requirements.
	 */
	public function update_ips();

	/**
	 * Delete the list of trusted proxy IP addresses.
	 *
	 * @return bool Returns true if the deletion was successful, false otherwise.
	 */
	public function delete_ips(): bool;
}