<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

wp_enqueue_media();

$out_link_utm='?utm_source='.urlencode(home_url()).'&amp;utm_medium=link&amp;utm_campaign=fb_og_wp_plugin';

?>
<div class="wrap" id="webdados_fb_admin">


	<h1><?php echo WEBDADOS_FB_PLUGIN_NAME ?> (<?php echo WEBDADOS_FB_VERSION; ?>)</h1><br class="clear"/>
	<p><?php _e( 'Please set some default values and which tags should, or should not, be included. It may be necessary to exclude some tags if other plugins are already including them.', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></p>

	<div class="columns-2 webdados_fb_admin_left" id="post-body">
		<div class="menu_div metabox-holder" id="tabs">
			<form id="webdados_fb_form" action="options.php" method="post">
				
				<?php settings_fields( 'wonderm00n_open_graph_settings' ); ?>
				<!-- Remeber last active tab -->
				<input type="hidden" name="wonderm00n_open_graph_settings[settings_last_tab]" id="settings_last_tab" value="<?php echo intval($options['settings_last_tab']); ?>"/>
				<!-- Minimum image size -->
				<input type="hidden" name="wonderm00n_open_graph_settings[fb_image_min_size]" value="<?php echo intval($options['fb_image_min_size']); ?>"/>
				
				<h2 class="nav-tab-wrapper">
					<ul>
						<li>
							<a class="nav-tab" href="#tabs-1" data-tab-index="0">
								<i class="dashicons-before dashicons-admin-generic"></i>
								<?php _e( 'General', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?>
							</a>
						</li>
						<li>
							<a class="nav-tab" href="#tabs-2" data-tab-index="1">
								<i class="dashicons-before dashicons-facebook-alt"></i>
								<?php _e( 'Open Graph', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?>
							</a>
						</li>
						<li>
							<a class="nav-tab" href="#tabs-3" data-tab-index="2">
								<i class="dashicons-before dashicons-twitter"></i>
								<?php _e( 'Cards', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?>
							</a>
						</li>
						<li>
							<a class="nav-tab" href="#tabs-4" data-tab-index="3">
								<i class="dashicons-before dashicons-googleplus"></i>
								<?php _e( 'Schema', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?>
							</a>
						</li>
						<li>
							<a class="nav-tab" href="#tabs-5" data-tab-index="4">
								<i class="dashicons-before dashicons-admin-site"></i>
								<?php _e( 'SEO tags', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?>
							</a>
						</li>
						<li>
							<a class="nav-tab" href="#tabs-6" data-tab-index="5">
								<i class="dashicons-before dashicons-layout"></i>
								<?php _e( '3rd party', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?>
							</a>
						</li>
						<li>
							<a class="nav-tab" href="#tabs-7" data-tab-index="6">
								<i class="dashicons-before dashicons-admin-tools"></i>
								<?php _e( 'Tools', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?>
							</a>
						</li>
					</ul>
				</h2>

				<div id="poststuff">

					<div class="clear"></div>
				
					<!-- General -->
					<?php include 'options-page-general.php'; ?>
					
					
					<!-- Facebook Open Graph -->
					<?php include 'options-page-facebook.php'; ?>
					
					<!-- Twitter Cards -->
					<?php include 'options-page-twitter.php'; ?>
					
					<!-- Google+ / Schema.org -->
					<?php include 'options-page-schema.php'; ?>
					
					<!-- SEO Meta Tags -->
					<?php include 'options-page-seo.php'; ?>
					
					<!-- 3rd party integrations -->
					<?php include 'options-page-3rdparty.php'; ?>
					
					<!-- Tools -->
					<?php include 'options-page-tools.php'; ?>

					<div class="clear"></div>
					<?php submit_button(); ?>

				</div>

			</form>
		</div>
	</div>


	<?php include 'options-page-right.php'; ?>
	

	<div class="clear">
		<p><br/>&copy; 2011-<?php echo date( 'Y' ); ?> <a href="https://www.webdados.pt/<?php echo esc_attr($out_link_utm); ?>" target="_blank">Webdados</a> &amp; <a href="https://wonderm00n.com/<?php echo esc_attr($out_link_utm); ?>" target="_blank">Marco Almeida (Wonderm00n)</a></p>
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
