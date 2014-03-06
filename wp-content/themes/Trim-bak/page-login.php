<?php
/*
Template Name: Login Page
*/
?>
<?php
	$et_ptemplate_settings = array();
	$et_ptemplate_settings = maybe_unserialize( get_post_meta(get_the_ID(),'et_ptemplate_settings',true) );

	$fullwidth = isset( $et_ptemplate_settings['et_fullwidthpage'] ) ? (bool) $et_ptemplate_settings['et_fullwidthpage'] : false;
?>

<?php get_header(); ?>

<div id="main_content" class="clearfix<?php if ( $fullwidth ) echo ' fullwidth'; ?>">
	<div id="left_area">
		<?php get_template_part('includes/breadcrumbs', 'page'); ?>

		<?php get_template_part('loop', 'page'); ?>

		<div id="et-login" class="responsive">
			<div class='et-protected'>
				<div class='et-protected-form'>
					<?php $scheme = apply_filters( 'et_forms_scheme', null ); ?>

					<form action='<?php echo esc_url( home_url( '', $scheme ) ); ?>/wp-login.php' method='post'>
						<p><label><span><?php esc_html_e('Username','Trim'); ?>: </span><input type='text' name='log' id='log' value='<?php echo esc_attr($user_login); ?>' size='20' /><span class='et_protected_icon'></span></label></p>
						<p><label><span><?php esc_html_e('Password','Trim'); ?>: </span><input type='password' name='pwd' id='pwd' size='20' /><span class='et_protected_icon et_protected_password'></span></label></p>
						<input type='submit' name='submit' value='Login' class='etlogin-button' />
					</form>
				</div> <!-- .et-protected-form -->
			</div> <!-- .et-protected -->
		</div> <!-- end #et-login -->

		<div class="clear"></div>

		<?php if ( 'on' == get_option('trim_show_pagescomments') ) comments_template('', true); ?>
	</div> <!-- end #left_area -->

	<?php if ( ! $fullwidth ) get_sidebar(); ?>
</div> <!-- end #main_content -->

<?php get_footer(); ?>