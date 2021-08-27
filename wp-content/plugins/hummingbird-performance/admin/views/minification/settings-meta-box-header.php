<?php
/**
 * Settings meta box header.
 *
 * @var string $title
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h3  class="sui-box-title"><?php echo esc_html( $title ); ?></h3>

<?php if ( ! \Hummingbird\Core\Utils::is_member() ) : ?>
	<div class="sui-actions-right">
		<a class="sui-button sui-button-green" href="<?php echo \Hummingbird\Core\Utils::get_link( 'plugin', 'hummingbird_assetoptimization_settings_upgrade_button' ); ?>" target="_blank">
			<?php _e( 'Upgrade to PRO', 'wphb' ); ?>
		</a>
	</div>
<?php endif; ?>
