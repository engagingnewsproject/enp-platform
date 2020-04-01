<?php
		$_error = NULL;
		if (array_key_exists('error', $_REQUEST)) {
			$_error = $_REQUEST['error'];
		}
?>
<div class="wrap">
	<header>
	<a href="http://wpengine.com/"><img src="<?php echo plugins_url($this->getPluginLogo(), __FILE__); ?>" width="180px" /></a>
  <p class="poweredBy u-pull-right"><a href="http://blogvault.net"><img src="<?php echo plugins_url('../assets/img/blogvault-logo-120.png', __FILE__); ?>" width="140px"/></a></p>
	</header>

	<hr/>
	<div class="row">

		<div class="seven columns">
			<form id="wpe_migrate_form" dummy=">" action="<?php echo $this->bvinfo->appUrl(); ?>/home/migrate" onsubmit="document.getElementById('migratesubmit').disabled = true;" method="post" name="signup">
				<h1>Migrate My Site to WP Engine</h1>
				<p>The WP Engine Automated Migration plugin allows you to easily migrate your entire WordPress site from
					your previous hosting service to WP Engine for free.</p>
				<p>Take the information from the migration page of your <a href="http://my.wpengine.com">WP Engine User Portal</a>, and paste
				those values into the fields below, and click "Migrate".</p>
				<p style="color:red">The .htaccess file will not be supported on our platform on PHP 7.4 and up (PHP 7.4 is currently default). We will continue to support .htaccess as normal on all previous versions of PHP until they are deprecated. For more information regarding these .htaccess changes, or to find/change your environment’s PHP version, <a href="https://wpengine.com/support/php-guide/">check out our PHP guide!</a></p>
<?php if ($_error == "email") {
	echo '<div class="error" style="padding-bottom:0.5%;"><p>There is already an account with this email.</p></div>';
} else if ($_error == "blog") {
	echo '<div class="error" style="padding-bottom:0.5%;"><p>Could not create an account. Please contact <a href="http://blogvault.net/contact/">blogVault Support</a><br />
		<font color="red">NOTE: We do not support automated migration of locally hosted sites.</font></p></div>';
} else if (($_error == "custom") && isset($_REQUEST['bvnonce']) && wp_verify_nonce($_REQUEST['bvnonce'], "bvnonce")) {
	echo '<div class="error" style="padding-bottom:0.5%;"><p>'.base64_decode($_REQUEST['message']).'</p></div>';
}
?>
				<input type="hidden" name="bvsrc" value="wpplugin" />
				<input type="hidden" name="migrate" value="wpengine" />
				<input type="hidden" name="type" value="sftp" />
<?php echo $this->siteInfoTags(); ?>
				<div class="row">
						<div class="six columns">
								<label id='label_email'>Email</label>
								<input class="u-full-width" type="text" id="email" name="email">
								<p class="help-block"></p>
						</div>
						<div class="six columns">
								<label class="control-label" for="input02">Destination Site URL</label>
								<input type="text" class="u-full-width" name="newurl" placeholder="site.wpengine.com">
					</div>
				</div>
				<div class="row">
					<div class="six columns">
								<label class="control-label" for="inputip"> SFTP Host/Server Address </label>
								<input type="text" class="u-full-width" placeholder="ex. 123.456.789.101" name="address">
								<p class="help-block"></p>
					</div>
				</div>
				<div class="row">
					<div class="six columns">
						<label class="control-label" for="input01">SFTP Username</label>
								<input type="text" class="u-full-width" placeholder="See WP Engine User Portal" name="username">
								<p class="help-block"></p>
					</div>
					<div class="six columns">
						<label class="control-label" for="input02">SFTP Password</label>
								<input type="password" class="u-full-width" placeholder="See WP Engine User Portal" name="passwd">
					</div>
				</div>
					<hr/>

							<h3>Is Your Site Password Protected?</h3>
							<p>If your current host or your WP Engine install is password protected, you'll need to enter that information here so
							that the migration plugin can access all of your site.
							</p>

								<button name="password-protected" id="advanced-options-toggle" class="button" onclick="javascript; return false">My site is password protected</button>

							<div id="password-auth" style="display:none">
								<div id="source-auth" class="six columns">
									<div class="row">
										<div class="twelve columns">
											<h3>Current</h3>
											<label class="control-label" for="httpauth_src_user">User</label>
											<input type="text" class="u-full-width" name="httpauth_src_user">
											<p class="help-block"></p>
										</div>
									</div>
									<div class="row">
										<div class="twelve columns">
											<label class="control-label" for="httpauth_src_password">Password</label>
											<input type="password" class="u-full-width" name="httpauth_src_password">
											<p class="help-block sourceAuthError error" style="display:none">It appears that your current site that does not exist on WP Engine is password protected. Please provide your username and password
											   for this password protection.</p>
										</div>
									</div>
								</div>

							<div id="dest-auth" class="six columns">
								<div class="row">
									<div class="twelve columns">
										<h3>WP Engine</h3>
										<label class="control-label" for="httpauth_dest_user">Username</label>
										<input type="text" class="u-full-width" name="httpauth_dest_user">
										<p class="help-block"></p>
									</div>
								</div>
								<div class="row">
									<div class="twelve columns">
										<label class="control-label" for="httpauth_dest_password">Password</label>
										<input type="password" class="u-full-width" name="httpauth_dest_password">
										<p class="help-block destAuthError error" style="display:none">It appears that your site on WP Engine is password protected. Please provide your username and password
											 for the password protection.</p>
									</div>
								</div>
						</div>
					</div>

				<hr/>
					<div>
						<input type="checkbox" style="width:16px; height:16px" name="consent" onchange="document.getElementById('migratesubmit').disabled = !this.checked;" value="1"/>I agree to WP Engine's <a href="https://wpengine.com/terms-of-service/" target="_blank" rel="noopener noreferrer">Terms of Service</a>
					</div>
						<br><input type='submit' disabled id='migratesubmit' value='Migrate' class="button button-primary">
			</form>
		</div>

			<div class="five columns">
				<h1>Resources</h1>
				<div style="padding:10px; background-color:#FFF; margin-top:15px;">
					<iframe src="//fast.wistia.net/embed/iframe/0rrkl3w1vu?videoFoam=true" allowtransparency="true" frameborder="0" scrolling="no" class="wistia_embed" name="wistia_embed" allowfullscreen mozallowfullscreen webkitallowfullscreen oallowfullscreen msallowfullscreen width="500" height="313"></iframe><script src="//fast.wistia.net/assets/external/E-v1.js"></script>
					<p><i>For full instructions and solutions to common errors, please visit our <a href="http://wpengine.com/support/wp-engine-automatic-migration/">WP Engine Automated Migration</a> support garage article.</i></p>
				</div>
			</div>
		</div><!--row end-->
	</div><!-- wrap ends here -->

	<script type="text/javascript">
		jQuery(document).ready(function () {
			<?php if (array_key_exists('auth_required_dest', $_REQUEST)) { ?>
					jQuery('#password-auth').show();
					jQuery('.sourceAuthError').show();
					jQuery('#dest-auth').addClass("attentionNeeded");
			<?php } ?>

			<?php if (array_key_exists('auth_required_source', $_REQUEST)) { ?>
					jQuery('#password-auth').show();
					jQuery('.destAuthError').show();
					jQuery('#source-auth').addClass("attentionNeeded");
			<?php } ?>
			jQuery('#advanced-options-toggle').click(function() {
				jQuery('#password-auth').toggle();
				return false;
			});
		});
	</script>