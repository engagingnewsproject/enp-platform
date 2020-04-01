<?php

	$links = array(
		0	=>	array(
			'text'	=>	__('Test your URLs on the Facebook Debugger', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://developers.facebook.com/tools/debug',
		),
		5	=>	array(
			'text'	=>	__('Test your URLs on the Twitter Card validator', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://cards-dev.twitter.com/validator',
		),
		10	=>	array(
			'text'	=>	__('About the Open Graph Protocol (on Facebook)', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://developers.facebook.com/docs/opengraph/',
		),
		20	=>	array(
			'text'	=>	__('The Open Graph Protocol (official website)', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'http://ogp.me/',
		),
		25	=>	array(
			'text'	=>	__('About Twitter Cards', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://dev.twitter.com/cards/getting-started',
		),
		30	=>	array(
			'text'	=>	__('Plugin official URL', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://www.webdados.pt/wordpress/plugins/facebook-open-graph-meta-tags-wordpress/'.$out_link_utm,
		),
		40	=>	array(
			'text'	=>	__('Author\'s website: Webdados', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://www.webdados.pt/'.$out_link_utm,
		),
		50	=>	array(
			'text'	=>	__('Author\'s Facebook page: Webdados', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://www.facebook.com/Webdados',
		),
		60	=>	array(
			'text'	=>	__('Author\'s Twitter account: @Wonderm00n<br/>(Webdados founder)', 'wonderm00ns-simple-facebook-open-graph-tags'),
			'url'	=>	'https://twitter.com/wonderm00n',
		),
	);

?>
<div class="postbox-container webdados_fb_admin_right">

		<div id="poststuff">

			<div class="postbox">
				<h3 class="hndle"><?php _e('About this plugin', 'wonderm00ns-simple-facebook-open-graph-tags');?></h3>
				<div class="inside">
					<h4><?php _e('Support forum', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>:</h4>
					<p><a href="https://wordpress.org/support/plugin/wonderm00ns-simple-facebook-open-graph-tags" target="_blank">WordPress.org</a></p>
					<h4><?php _e('Premium technical support or custom WordPress development', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>:</h4>
					<p id="webdadoslink"><a href="https://www.webdados.pt/contactos/<?php echo esc_attr($out_link_utm); ?>" title="<?php echo esc_attr( sprintf( __( 'Please contact %s', 'wonderm00ns-simple-facebook-open-graph-tags' ), 'Webdados' ) ); ?>" target="_blank"><img src="<?php echo plugins_url( 'webdados.svg', __FILE__ ); ?>" width="200"/></a></p>
					<h4><?php _e('Please rate our plugin at WordPress.org', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>:</h4>
					<a href="https://wordpress.org/support/view/plugin-reviews/wonderm00ns-simple-facebook-open-graph-tags?filter=5#postform" target="_blank" style="text-align: center; display: block;">
						<div class="star-rating"><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div><div class="star star-full"></div></div>
					</a>
				</div>
			</div>

			<div class="postbox">
				<h3 class="hndle"><?php _e('Useful links', 'wonderm00ns-simple-facebook-open-graph-tags');?></h3>
				<div class="inside">
					<ul>
						<?php foreach($links as $link) { ?>
							<li>- <a href="<?php echo $link['url']; ?>" target="_blank"><?php echo $link['text']; ?></a></li>
						<?php } ?>
					</ul>
				</div>
			</div>

			<div id="webdados_fb_open_graph_donation" class="postbox">
				<h3 class="hndle"><?php _e('Donate', 'wonderm00ns-simple-facebook-open-graph-tags');?></h3>
				<div class="inside">
					<p><?php _e('If you find this plugin useful and want to make a contribution towards future development please consider making a small, or big ;-), donation.', 'wonderm00ns-simple-facebook-open-graph-tags');?></p>
					<center><form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
						<input type="hidden" name="cmd" value="_donations">
						<input type="hidden" name="business" value="wonderm00n@gmail.com">
						<input type="hidden" name="lc" value="PT">
						<input type="hidden" name="item_name" value="Marco Almeida (Wonderm00n)">
						<input type="hidden" name="item_number" value="facebook_open_graph_plugin">
						<select name="currency_code">
							<option value="USD"><?php _e('Donate in US Dollars', 'wonderm00ns-simple-facebook-open-graph-tags'); ?></option>
							<option value="EUR"><?php _e('Donate in Euros', 'wonderm00ns-simple-facebook-open-graph-tags'); ?></option>
						</select>
						<input type="hidden" name="bn" value="PP-DonationsBF:btn_donateCC_LG.gif:NonHosted">
						<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
						<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
					</form></center>
				</div>
			</div>
			
		</div>

</div>