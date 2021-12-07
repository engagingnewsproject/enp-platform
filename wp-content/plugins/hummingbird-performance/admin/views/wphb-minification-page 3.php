<?php
/**
 * Asset optimization page.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( is_multisite() && is_network_admin() ) {
	$this->do_meta_boxes( 'main' );
}

if ( $this->has_meta_boxes( 'box-enqueued-files-empty' ) ) {
	$this->do_meta_boxes( 'box-enqueued-files-empty' );
}

$this->do_meta_boxes( 'summary' );
?>

<?php if ( ! $this->has_meta_boxes( 'box-enqueued-files-empty' ) && ! is_network_admin() ) : ?>
	<div class="sui-row-with-sidenav">
		<?php $this->show_tabs(); ?>

		<?php if ( 'files' === $this->get_current_tab() ) : ?>
			<form id="wphb-minification-form" method="post">
				<?php do_action( 'wphb_asset_optimization_http2_notice' ); ?>
				<?php $this->do_meta_boxes( 'main' ); ?>
			</form>
			<?php if ( 'basic' === $this->mode ) : ?>
				<br>
				<div id="wrap-wphb-auto-minify"></div>
			<?php endif; ?>
		<?php endif; ?>

		<?php if ( 'tools' === $this->get_current_tab() ) : ?>
			<form id="wphb-minification-tools-form" method="post">
				<?php $this->do_meta_boxes( 'tools' ); ?>
			</form>
		<?php endif; ?>

		<?php if ( 'settings' === $this->get_current_tab() ) : ?>
			<form id="wphb-minification-settings-form" method="post">
				<?php $this->do_meta_boxes( 'settings' ); ?>
			</form>
		<?php endif; ?>

		<?php if ( 'import' === $this->get_current_tab() ) : ?>
			<?php $this->do_meta_boxes( 'import' ); ?>
		<?php endif; ?>
	</div><!-- end row -->
	<?php
endif;

if ( get_option( 'wphb-minification-show-advanced_modal' ) ) {
	$this->modal( 'minification-advanced' );
}
$this->modal( 'automatic-ao-how-does-it-work' );
$this->modal( 'manual-ao-how-does-it-work' );

if ( 'advanced' === $this->mode ) {
	$this->modal( 'minification-tour' );
	if ( get_option( 'wphb-minification-show-config_modal' ) ) {
		$this->modal( 'minification-basic' );
	}
}

$this->modal( 'found-assets' );

if ( ! \Hummingbird\Core\Utils::is_member() ) {
	$this->modal( 'membership' );
}

?>

<script>
	jQuery(document).ready( function() {
		window.WPHB_Admin.getModule( 'minification' );
		<?php if ( isset( $_GET['run'] ) ) : ?>
			jQuery( document ).trigger( 'check-files' );
		<?php endif; ?>
		<?php if ( 'import' === $this->get_current_tab() ) : ?>
			window.WPHB_Admin.getModule( 'settings' );
		<?php endif; ?>
	});
</script>
