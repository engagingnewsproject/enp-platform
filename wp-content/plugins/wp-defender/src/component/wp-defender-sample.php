<?php
/**
 * The file is used to generate and validate secret keys. Deleting this file or manually editing the content will
 * permanently break 2fa TOTP method for all users.
 *
 * @package WP_Defender
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}
// WP Defender - Start.
define( 'WP_DEFENDER_TOTP_KEY', '{{__REPLACE_CODE__}}' );
// WP Defender - End.