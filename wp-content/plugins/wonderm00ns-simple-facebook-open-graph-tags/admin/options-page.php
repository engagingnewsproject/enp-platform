<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

wp_enqueue_media();

$out_link_utm='?utm_source='.urlencode(home_url()).'&amp;utm_medium=link&amp;utm_campaign=fb_og_wp_plugin';

?>
<div class="wrap" id="webdados_fb_admin">


	<h1>
		<?php echo WEBDADOS_FB_PLUGIN_NAME ?> (<?php echo WEBDADOS_FB_VERSION; ?>)
		<?php do_action( 'fb_og_admin_settings_title' ); ?>
	</h1><br class="clear"/>
	<p><?php _e( 'Please set some default values and which tags should, or should not, be included. It may be necessary to exclude some tags if other plugins are already including them.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></p>

	<div class="columns-2 webdados_fb_admin_left" id="post-body">
		<div class="menu_div metabox-holder" id="tabs">
			<form id="webdados_fb_form" action="options.php" method="post">

				<?php settings_fields( 'wonderm00n_open_graph_settings' ); ?>

				<!-- Remember last active tab -->
				<input type="hidden" name="wonderm00n_open_graph_settings[settings_last_tab]" id="settings_last_tab" value="<?php echo intval($options['settings_last_tab']); ?>"/>
				<!-- Minimum image size -->
				<input type="hidden" name="wonderm00n_open_graph_settings[fb_image_min_size]" value="<?php echo intval($options['fb_image_min_size']); ?>"/>
				
				<h2 class="nav-tab-wrapper">
					<ul>
						<?php
						$settings_tabs = array(
							'1' => array(
								'icon'  => '<i class="dashicons-before dashicons-admin-generic"></i>',
								'title' => __( 'General', 'wonderm00ns-simple-facebook-open-graph-tags' ),
								'file'  => 'options-page-general.php'
							),
							'2' => array(
								'icon'  => '<i class="dashicons-before dashicons-facebook-alt"></i>',
								'title' => __( 'Open Graph', 'wonderm00ns-simple-facebook-open-graph-tags' ),
								'file'  => 'options-page-facebook.php'
							),
							'3' => array(
								'icon'  => '<i class="dashicons-before dashicons-twitter"></i>',
								'title' => __( 'Cards', 'wonderm00ns-simple-facebook-open-graph-tags' ),
								'file'  => 'options-page-twitter.php'
							),
							/*'4' => array(
								'icon'  => '<i class="dashicons-before dashicons-googleplus"></i>',
								'title' => __( 'Schema', 'wonderm00ns-simple-facebook-open-graph-tags' ).' ('.__( 'deprecated', 'wonderm00ns-simple-facebook-open-graph-tags' ).')',
								'file'  => 'options-page-schema.php'
							),*/
							'5' => array(
								'icon'  => '<i class="dashicons-before dashicons-admin-site"></i>',
								'title' => __( 'SEO tags', 'wonderm00ns-simple-facebook-open-graph-tags' ),
								'file'  => 'options-page-seo.php'
							),
							'6' => array(
								'icon'  => '<i class="dashicons-before dashicons-layout"></i>',
								'title' => __( '3rd party', 'wonderm00ns-simple-facebook-open-graph-tags' ),
								'file'  => 'options-page-3rdparty.php'
							),
							'7' => array(
								'icon'  => '<i class="dashicons-before dashicons-admin-tools"></i>',
								'title' => __( 'Tools', 'wonderm00ns-simple-facebook-open-graph-tags' ),
								'file'  => 'options-page-tools.php'
							)
						);
						//Show tabs
						foreach ( $settings_tabs as $key => $tab ) {
							$index = 0;
							?>
							<li>
								<a class="nav-tab" href="#tabs-<?php echo esc_attr( $key ); ?>" data-tab-index="<?php echo intval( $index ); ?>">
									<?php echo $tab['icon']; ?>
									<?php echo $tab['title']; ?>
								</a>
							</li>
							<?php
							$index++;
						}
						?>
					</ul>
				</h2>

				<div id="poststuff">

					<div class="clear"></div>

					<?php
					//Include files
					foreach ( $settings_tabs as $key => $tab ) {
						require_once( $tab['file'] );
					}
					?>

					<div class="clear"></div>
					<?php submit_button(); ?>

				</div>

			</form>
		</div>
	</div>


	<?php include 'options-page-right.php'; ?>
	

	<div class="clear">
		<p><br/>&copy; 2011-<?php echo date( 'Y' ); ?></p>
	</div>


</div>
<script type="text/javascript">
jQuery(document).ready(function($) {

		//Tabs
		$( function() {
			$( "#tabs" ).tabs({
				active: <?php echo intval($options['settings_last_tab']); ?>
			});
		});
		<?php
		if (isset($_GET['localeOnline']) && intval($_GET['localeOnline'])==1) {
			?>
			location.hash = "#fblocale";
			$('#fb_locale').focus();
			<?php
		}
		?>

});
</script>
