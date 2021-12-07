<?php
/**
 * Performance reports meta box header.
 *
 * @package Hummingbird
 */

use Hummingbird\Core\Utils;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<h3  class="sui-box-title">
	<?php echo esc_html( $title ); ?>
	<?php if ( ! Utils::is_member() ) : ?>
		<span class="sui-tag sui-tag-pro"><?php esc_html_e( 'Pro', 'wphb' ); ?></span>
	<?php endif; ?>
</h3>

<?php if ( ! Utils::is_member() ) : ?>
	<div class="sui-actions-right">
		<a class="sui-button sui-button-purple" href="<?php echo esc_url( Utils::get_link( 'plugin', 'hummingbird_test_upgrade_button' ) ); ?>" target="_blank">
			<?php esc_html_e( 'Upgrade to Pro', 'wphb' ); ?>
		</a>
	</div>
<?php endif; ?>
