<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>
<div class="menu_containt_div" id="tabs-7">
	<p><?php _e( 'Just some random tools', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></p>
	<div class="postbox">
		<h3 class="hndle"><i class="dashicons-before dashicons-format-image"></i> <?php _e( 'Image tools', 'wonderm00ns-simple-facebook-open-graph-tags' ) ?></h3>
		<div class="inside">
			<table class="form-table">
				<tbody>
					
					<tr>
						<th><?php _e( 'Clear all transients', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>:</th>
						<td>
							<input type="checkbox" name="tools[]" value="clear_transients"/>
							<!-- This is not a good idea because the page will always keep the run_tool variable -->
							<!--<a href="<?php echo esc_url($_SERVER['REQUEST_URI']); ?>&amp;run_tool=clear_transients" class="button fb-og-tool"><?php _e( 'Do it', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></a>-->
						</td>
					</tr>
					<tr>
						<td colspan="2" class="info">
							- <strong><?php _e( 'This is an advanced tool: Don\'t mess with this unless you know what you\'re doing', 'wonderm00ns-simple-facebook-open-graph-tags' ); ?></strong>
							<br/>
							- <?php _e( 'We use transients to cache the image sizes, so that we only have to calculate them once (a week). Because of some server issues it may happen that we cannot correctly get the image size and we\'ll cache that, meaning that we\'ll never try it again (for a week). This tool will delete ALL the transients and force the image size calculation to be done again for all images, as they\'re nedded.' , 'wonderm00ns-simple-facebook-open-graph-tags' ); ?>
						</td>
					</tr>
					
					

				</tbody>
			</table>
		</div>
	</div>
</div>