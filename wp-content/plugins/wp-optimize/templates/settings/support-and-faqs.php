<?php if (!defined('WPO_VERSION')) die('No direct access allowed'); ?>

<h3><?php esc_html_e('Support and feedback', 'wp-optimize'); ?></h3>
<div class="wpo-fieldgroup">
	<?php WP_Optimize()->include_template('settings/system-status.php'); ?>
	<ul>
		<li><?php WP_Optimize()->wp_optimize_url('https://getwpo.com/faqs/', __("Read our FAQ here", 'wp-optimize')); ?></li>
		<li><a href="https://wordpress.org/support/plugin/wp-optimize/" target="_blank"><?php esc_html_e('Support is available here.', 'wp-optimize'); ?></a></li>
		<li><?php echo esc_html__('If you like WP-Optimize,', 'wp-optimize').' <a href="https://wordpress.org/support/plugin/wp-optimize/reviews/?rate=5#new-post" target="_blank">'.esc_html__('please give us a positive review, here.', 'wp-optimize'); ?></a> <?php echo esc_html__('Or, if you did not like it,', 'wp-optimize').' <a target="_blank" href="https://wordpress.org/support/plugin/wp-optimize/">'.esc_html__('please tell us why at this link.', 'wp-optimize'); ?></a></li>
	</ul>
</div>