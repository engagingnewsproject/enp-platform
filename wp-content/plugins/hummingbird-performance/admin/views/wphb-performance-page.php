<?php
/**
 * Render page.
 *
 * @package Hummingbird
 *
 * @var $this Page
 *
 * @var array|wp_error $report  Report, set in render_inner_content().
 */

use Hummingbird\Admin\Page;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $this->has_meta_boxes( 'summary' ) ) {
	$this->do_meta_boxes( 'summary' );
} ?>

<?php if ( $report ) : ?>
	<?php $this->show_tabs_flat(); ?>
	<?php $this->do_meta_boxes( $this->get_current_tab() ); ?>
<?php else : ?>
	<?php $this->do_meta_boxes(); ?>
<?php endif; ?>

<?php $this->modal( 'add-recipient' ); ?>

<script>
	jQuery(document).ready( function() {
		window.WPHB_Admin.getModule( 'performance' );
	});
</script>
