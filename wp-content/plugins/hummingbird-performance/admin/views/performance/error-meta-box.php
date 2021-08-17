<?php
/**
 * Performance error meta box.
 *
 * @since 2.0.0  Isolated from other meta boxes.
 * @package Hummingbird
 *
 * @var string   $error_text     Error text.
 * @var string   $error_details  Error details.
 * @var string   $retry_url      Url to start a new performance scan.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$this->admin_notices->show_inline(
	$error_text,
	'error',
	'<code>' . $error_details . '</code>',
	sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
		esc_html__( '%1$sTry again%2$s', 'wphb' ),
		'<a href="' . esc_url( $retry_url ) . '" class="sui-button sui-button-blue">',
		'</a>'
	) . sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
		esc_html__( '%1$sSupport%2$s', 'wphb' ),
		'<a href="' . esc_url( \Hummingbird\Core\Utils::get_link( 'support' ) ) . '" target="_blank" class="sui-button sui-button-blue">',
		'</a>'
	)
);
