<?php
/**
 * Performance error meta box on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $error        Error text.
 * @var string $retry_url    URL to retry.
 * @var string $support_url  URL to support.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$this->admin_notices->show_inline(
	$error,
	'error',
	sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
		esc_html__( '%1$sTry again%2$s', 'wphb' ),
		'<a href="' . esc_url( $retry_url ) . '" class="sui-button">',
		'</a>'
	) . sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
		esc_html__( '%1$sSupport%2$s', 'wphb' ),
		'<a href="' . esc_url( $support_url ) . '" target="_blank" class="sui-button">',
		'</a>'
	)
);
