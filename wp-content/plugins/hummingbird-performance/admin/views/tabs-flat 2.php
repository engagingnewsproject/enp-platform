<?php
/**
 * Flat tabs template.
 *
 * We fake the use of SUI tabs, that's why we have the empty `data-panes` divs - to avoid JS errors.
 *
 * @since 3.0.0
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="sui-box">
	<div class="sui-box-header">
		<div class="sui-box-title sui-side-tabs sui-tabs">
			<div data-tabs>
				<?php foreach ( $this->get_tabs() as $tab_id => $name ) : ?>
					<a href="<?php echo esc_url( $this->get_tab_url( $tab_id ) ); ?>" class="<?php echo ( $tab_id === $this->get_current_tab() ) ? 'active' : null; ?>">
						<?php echo esc_html( $name ); ?>
					</a>
				<?php endforeach; ?>
			</div>
			<div data-panes class="sui-hidden">
				<?php foreach ( $this->get_tabs() as $element ) : ?>
					<div></div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php do_action( 'wphb_admin_after_flat_tab_' . $this->get_slug() ); ?>
	</div>
</div>

