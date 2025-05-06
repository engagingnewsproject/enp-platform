<?php
$membership_type       = WPMUDEV_Dashboard::$api->get_membership_status();
$is_hosted_third_party = WPMUDEV_Dashboard::$api->is_hosted_third_party();
$hide_footer           = false;
// translators: %s heart icon.
$footer_text         = sprintf( __( 'Made with %s by WPMU DEV', 'wpmudev' ), ' <i class="sui-icon-heart"></i>' );
$whitelabel_settings = WPMUDEV_Dashboard::$whitelabel->get_settings();
$hide_footer         = $whitelabel_settings['footer_enabled'];
$footer_text         = apply_filters( 'wpmudev_branding_footer_text', $footer_text );

$footer_nav_links = array(
	array(
		'href' => 'https://wpmudev.com/hub2/',
		'name' => __( 'The Hub', 'wpmudev' ),
	),
	array(
		'href' => 'https://wpmudev.com/projects/category/plugins/',
		'name' => __( 'Plugins', 'wpmudev' ),
	),
	array(
		'href' => 'https://wpmudev.com/roadmap/',
		'name' => __( 'Roadmap', 'wpmudev' ),
	),
	array(
		'href' => 'https://wpmudev.com/hub/support',
		'name' => __( 'Support', 'wpmudev' ),
	),
	array(
		'href' => 'https://wpmudev.com/docs/',
		'name' => __( 'Docs', 'wpmudev' ),
	),
	array(
		'href' => 'https://wpmudev.com/hub2/community/',
		'name' => __( 'Community', 'wpmudev' ),
	),
);

if ( 'free' === $membership_type && $is_hosted_third_party ) {
	$footer_nav_links = array(
		array(
			'href' => 'https://profiles.wordpress.org/wpmudev#content-plugins',
			'name' => __( 'Free Plugins', 'wpmudev' ),
		),
		array(
			'href' => 'https://wpmudev.com/features/',
			'name' => __( 'Membership', 'wpmudev' ),
		),
		array(
			'href' => 'https://wpmudev.com/roadmap/',
			'name' => __( 'Roadmap', 'wpmudev' ),
		),
		array(
			'href' => 'https://wpmudev.com/docs/',
			'name' => __( 'Docs', 'wpmudev' ),
		),
		array(
			'href' => 'https://wpmudev.com/hub-welcome/',
			'name' => __( 'The Hub', 'wpmudev' ),
		),

	);
}

$footer_nav_links[] = array(
	'href' => 'https://wpmudev.com/terms-of-service/',
	'name' => __( 'Terms of Service', 'wpmudev' ),
);
$footer_nav_links[] = array(
	'href' => 'https://incsub.com/privacy-policy/',
	'name' => __( 'Privacy Policy', 'wpmudev' ),
);

/**
 * Action hook to render something before SUI footer.
 *
 * @since 4.11
 */
do_action( 'wpmudev_dashboard_ui_before_footer' );
?>
<div class="sui-footer"><?php echo $footer_text; // phpcs:ignore ?></div>

<?php if ( ! $hide_footer ) : ?>
	<ul class="sui-footer-nav">
		<?php foreach ( $footer_nav_links as $footer_nav_link ) : ?>
			<li><a href="<?php echo esc_url( $footer_nav_link['href'] ); ?>" target="_blank"><?php echo esc_html( $footer_nav_link['name'] ); ?></a></li>
		<?php endforeach; ?>
	</ul>
	<ul class="sui-footer-social">
		<li>
			<a href="https://www.facebook.com/wpmudev" target="_blank">
				<i class="sui-icon-social-facebook" aria-hidden="true"></i>
				<span class="sui-screen-reader-text"><?php esc_html_e( 'Facebook', 'wpmudev' ); ?></span>
			</a>
		</li>
		<li>
			<a href="https://twitter.com/wpmudev" target="_blank">
				<i class="sui-icon-social-twitter" aria-hidden="true"></i>
			</a>
			<span class="sui-screen-reader-text"><?php esc_html_e( 'Twitter', 'wpmudev' ); ?></span>
		</li>
		<li>
			<a href="https://www.instagram.com/wpmu_dev/" target="_blank">
				<i class="sui-icon-instagram" aria-hidden="true"></i>
				<span class="sui-screen-reader-text"><?php esc_html_e( 'Instagram', 'wpmudev' ); ?></span>
			</a>
		</li>
	</ul>
<?php endif;
/**
 * Action hook to render something after SUI footer.
 *
 * @since 4.11
 */
do_action( 'wpmudev_dashboard_ui_after_footer' );
?>