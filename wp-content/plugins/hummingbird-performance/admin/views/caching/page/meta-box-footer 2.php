<?php
/**
 * Page caching meta box footer.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-actions-right">
	<span class="spinner"></span>
	<input type="submit" class="sui-button sui-button-blue" name="submit" value="<?php esc_attr_e( 'Save Settings', 'wphb' ); ?>">
</div>
