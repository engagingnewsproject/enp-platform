<div class="wrap ga-wrap">
    <h2>Google Analytics - <?php _e( 'Dashboard' ); ?></h2>
    <div class="ga_container" id="exTab2">
		<?php echo $data ?>
    </div>

	<?php if (empty(get_option('googleanalytics-hide-review'))) : ?>
		<div class="ga-review-us">
			<h3>
				<?php echo esc_html__( 'Love this plugin?', 'googleanalytics' ); ?>
				<br>
				<a href="https://wordpress.org/support/plugin/googleanalytics/reviews/#new-post">
					<?php echo esc_html__( 'Please spread the word by leaving us a 5 star review!', 'googleanalytics' ); ?>
				</a>
				<p>
					<div id="close-review-us">close</div>
				</p>
			</h3>
		</div>
	<?php endif; ?>
</div>
<script type="text/javascript">
	const GA_NONCE = '<?php echo wp_create_nonce( 'ga_ajax_data_change' ); ?>';
	const GA_NONCE_FIELD = '<?php echo Ga_Admin_Controller::GA_NONCE_FIELD_NAME; ?>';
</script>