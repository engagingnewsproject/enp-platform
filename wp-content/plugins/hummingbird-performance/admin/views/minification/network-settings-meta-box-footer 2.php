<?php
/**
 * Network settings meta box footer.
 *
 * @since 2.0.0
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-actions-right">
	<span class="spinner"></span>
	<input type="submit" class="sui-button sui-button-blue" id="wphb-ao-network-settings" value="<?php esc_attr_e( 'Save settings', 'wphb' ); ?>">
</div>
