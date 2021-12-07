<?php
/**
 * Caching page layout.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $this->has_meta_boxes( 'main' ) ) {
	$this->do_meta_boxes( 'main' );
}

$forms = array( 'page_cache', 'rss', 'settings' );
?>

<div class="sui-row-with-sidenav">
	<?php $this->show_tabs(); ?>

	<?php if ( 'caching' === $this->get_current_tab() ) : ?>
		<div class="box-caching-status" id="wrap-wphb-browser-caching"></div><br>
	<?php endif; ?>

	<?php if ( in_array( $this->get_current_tab(), $forms, true ) ) : ?>
		<form id="<?php echo esc_attr( $this->get_current_tab() ); ?>-form" method="post">
			<?php $this->do_meta_boxes( $this->get_current_tab() ); ?>
		</form>
	<?php else : ?>
		<?php $this->do_meta_boxes( $this->get_current_tab() ); ?>
	<?php endif; ?>
</div>

<?php
if ( 'caching' === $this->get_current_tab() || 'integrations' === $this->get_current_tab() ) {
	$this->modal( 'integration-cloudflare' );
}
?>

<script>
	jQuery(document).ready( function() {
		if ( window.WPHB_Admin ) {
			window.WPHB_Admin.getModule( 'caching' );
		}
	});
</script>
