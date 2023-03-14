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
	);

?>
<div class="postbox-container webdados_fb_admin_right">

		<div id="poststuff">

			<div class="postbox">
				<h3 class="hndle"><?php _e('About this plugin', 'wonderm00ns-simple-facebook-open-graph-tags');?></h3>
				<div class="inside">
					<h4><?php _e('Support forum', 'wonderm00ns-simple-facebook-open-graph-tags'); ?>:</h4>
					<p><a href="https://wordpress.org/support/plugin/wonderm00ns-simple-facebook-open-graph-tags" target="_blank">WordPress.org</a></p>
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
			
		</div>

</div>