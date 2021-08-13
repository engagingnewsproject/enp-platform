<?php
/**
 * Tabs template
 *
 * @package Hummingbird
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div role="navigation" class="sui-sidenav">
	<ul class="sui-vertical-tabs sui-sidenav-hide-md">
		<?php foreach ( $this->get_tabs() as $tab_id => $name ) : ?>
			<li class="sui-vertical-tab <?php echo ( $tab_id === $this->get_current_tab() ) ? 'current' : null; ?>">
				<a href="<?php echo esc_url( $this->get_tab_url( $tab_id ) ); ?>">
					<?php echo esc_html( $name ); ?>
				</a>
				<?php do_action( 'wphb_admin_after_tab_' . $this->get_slug(), $tab_id ); ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<div class="sui-sidenav-hide-lg">
		<select class="sui-mobile-nav" style="margin-bottom: 20px">
			<?php foreach ( $this->get_tabs() as $tab_id => $name ) : ?>
				<option value="<?php echo esc_url( $this->get_tab_url( $tab_id ) ); ?>" <?php selected( $this->get_current_tab(), $tab_id ); ?>>
					<?php echo esc_html( $name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</div>
</div>
