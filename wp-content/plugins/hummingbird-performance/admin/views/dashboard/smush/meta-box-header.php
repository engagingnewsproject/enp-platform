<?php
/**
 * Smush meta box header on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $title  Reports module title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3 class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<?php if ( ! \Hummingbird\Core\Utils::is_member() && \Hummingbird\Core\Utils::is_dash_logged_in() ) : ?>
	<div class="sui-actions-right">
		<a class="sui-button sui-button-green" href="<?php echo \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_dash_smush_header_upsell_link' ); ?>" target="_blank">
			<?php esc_html_e( 'Upgrade to Pro', 'wphb' ); ?>
		</a>
	</div>
<?php endif; ?>
