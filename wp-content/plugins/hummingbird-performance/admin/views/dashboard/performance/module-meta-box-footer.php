<?php
/**
 * Performance meta box footer on dashboard page.
 *
 * @package Hummingbird
 *
 * @since 1.7.0
 *
 * @var bool   $dismissed  Is the report dismissed.
 * @var string $url        Url to performance module.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<?php if ( $dismissed ) : ?>
	<a href="<?php echo esc_url( $url ); ?>" class="sui-button sui-button-ghost">
		<span class="sui-icon-wrench-tool" aria-hidden="true"></span>
		<?php esc_html_e( 'Configure', 'wphb' ); ?>
	</a>
<?php else : ?>
	<a href="<?php echo esc_url( $url ); ?>" class="sui-button sui-button-ghost">
		<span class="sui-icon-eye" aria-hidden="true"></span>
		<?php esc_html_e( 'View Full Report', 'wphb' ); ?>
	</a>
<?php endif; ?>
