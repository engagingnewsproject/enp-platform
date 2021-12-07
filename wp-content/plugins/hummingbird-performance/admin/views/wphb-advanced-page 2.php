<?php
/**
 * Advanced tools.
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-row-with-sidenav">
	<?php $this->show_tabs(); ?>

	<?php if ( 'main' === $this->get_current_tab() ) : ?>
		<form id="advanced-general-settings" method="post">
			<?php $this->do_meta_boxes( 'main' ); ?>
		</form>
	<?php elseif ( 'db' === $this->get_current_tab() ) : ?>
		<form id="advanced-db-settings" method="post">
			<?php $this->do_meta_boxes( 'db' ); ?>
		</form>
	<?php elseif ( 'lazy' === $this->get_current_tab() ) : ?>
		<form id="advanced-lazy-settings" method="post">
			<?php $this->do_meta_boxes( 'lazy' ); ?>
		</form>
	<?php else : ?>
		<?php $this->do_meta_boxes( $this->get_current_tab() ); ?>
	<?php endif; ?>
</div>

<?php $this->modal( 'site-health-orphaned' ); ?>

<script>
	jQuery(document).ready( function() {
		if ( window.WPHB_Admin ) {
			window.WPHB_Admin.getModule( 'advanced' );
		}
	});
</script>
