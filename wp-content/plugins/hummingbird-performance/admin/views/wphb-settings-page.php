<?php
/** @var \Hummingbird\Admin\Pages\Settings $this */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="sui-row-with-sidenav">
	<?php $this->show_tabs(); ?>
	<?php $this->do_meta_boxes( $this->get_current_tab() ); ?>
	<?php if ( 'configs' === $this->get_current_tab() ) : ?>
		<div id="wrap-wphb-configs"></div>
	<?php endif; ?>
</div>

<script>
	jQuery(document).ready( function() {
		window.WPHB_Admin.getModule( 'settings' );
	});
</script>
