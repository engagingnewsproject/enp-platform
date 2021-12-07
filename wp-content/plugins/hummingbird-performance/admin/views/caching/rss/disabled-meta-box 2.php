<?php
/**
 * Rss caching meta box (disabled state).
 *
 * @since 1.8
 * @package Hummingbird
 *
 * @var string $url  Activate/deactivate link.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<p>
	<?php esc_html_e( 'By default, WordPress will cache your RSS feeds to reduce the load on your server – which is a great feature. Hummingbird gives you control over the expiry time, or you can disable it all together.', 'wphb' ); ?>
</p>

<?php
$this->admin_notices->show_inline(
	esc_html__( 'RSS Caching is currently disabled.', 'wphb' ),
	'warning',
	sprintf( /* translators: %1$s - opening a tag, %2$s - </a> */
		esc_html__( '%1$sEnable Caching%2$s', 'wphb' ),
		'<a href="' . esc_url( $url ) . '" class="sui-button" role="button" onclick="WPHB_Admin.Tracking.enableFeature( \'RSS Caching\' )">',
		'</a>'
	)
);

