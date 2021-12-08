<?php
/**
 * Reports meta box header on dashboard page.
 *
 * @package Hummingbird
 *
 * @var string $title  Reports module title.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>
<?php if ( ! \Hummingbird\Core\Utils::is_member() ) : ?>
	<span class="sui-tag sui-tag-pro" style="margin-left: 10px">
		<?php esc_html_e( 'Pro', 'wphb' ); ?>
	</span>

	<div class="sui-actions-right">
		<a class="sui-button sui-button-purple" href="<?php echo esc_url( \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_dash_reports_upgrade_button' ) ); ?>" target="_blank">
			<?php esc_html_e( 'Upgrade to PRO', 'wphb' ); ?>
		</a>
	</div>
<?php endif; ?>
