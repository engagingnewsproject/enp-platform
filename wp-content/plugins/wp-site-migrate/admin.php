<?php
global $blogvault;
global $bvNotice;
global $bvWPEAdminPage;
global $bvAppUrl;
$bvNotice = '';
$bvWPEAdminPage = 'wpe-automated-migration';
if (defined('BV_APP_URL')) {
	$bvAppUrl = BV_APP_URL;
} else {
	$bvAppUrl = 'https://wpengine.blogvault.net';
}

if (!function_exists('bvWPEAddStyleSheet')) :
	function bvWPEAddStyleSheet($hook) {
		if('toplevel_page_wpe-automated-migration' != $hook){
			return;
		}

		wp_register_style('bvwpe_normalize', plugins_url('assets/css/normalize.css',__FILE__ ));
		wp_register_style('bvwpe_skeleton', plugins_url('assets/css/skeleton.css',__FILE__ ));
		wp_register_style('bvwpe_form-styles', plugins_url('assets/css/form-styles.css',__FILE__ ));
		wp_enqueue_style('bvwpe_normalize');
		wp_enqueue_style('bvwpe_skeleton');
		wp_enqueue_style('bvwpe_form-styles');
	}
add_action( 'admin_enqueue_scripts','bvWPEAddStyleSheet');
endif;

if (!function_exists('bvWPEAdminInitHandler')) :
	function bvWPEAdminInitHandler() {
		global $bvNotice, $blogvault, $bvWPEAdminPage, $bvAppUrl;
		global $sidebars_widgets;
		global $wp_registered_widget_updates;

		if (!current_user_can('activate_plugins'))
			return;

		if (isset($_REQUEST['bvnonce']) && wp_verify_nonce($_REQUEST['bvnonce'], "bvnonce")) {
			if (isset($_REQUEST['blogvaultkey']) && isset($_REQUEST['page']) && $_REQUEST['page'] == $bvWPEAdminPage) {
				if ((strlen($_REQUEST['blogvaultkey']) == 64)) {
					$keys = str_split($_REQUEST['blogvaultkey'], 32);
					$blogvault->updateKeys($keys[0], $keys[1]);
					bvActivateHandler();
					$bvNotice = "<b>Activated!</b> blogVault is now backing up your site.<br/><br/>";
					if (isset($_REQUEST['redirect'])) {
						$location = $_REQUEST['redirect'];
						wp_redirect($bvAppUrl."/migration/".$location);
						exit();
					}
				} else {
					$bvNotice = "<b style='color:red;'>Invalid request!</b> Please try again with a valid key.<br/><br/>";
				}
			}
		}

		if ($blogvault->getOption('bvActivateRedirect') === 'yes') {
			$blogvault->updateOption('bvActivateRedirect', 'no');
			wp_redirect($blogvault->bvAdminUrl($bvWPEAdminPage));
		}
	}
	add_action('admin_init', 'bvWPEAdminInitHandler');
endif;

if (!function_exists('bvWpeAdminMenu')) :
	function bvWpeAdminMenu() {
		global $bvWPEAdminPage;
		add_menu_page('WP Engine Migrate', 'Site Migration', 'manage_options', $bvWPEAdminPage, 'bvWpEMigrate', plugins_url( 'assets/images/favicon.ico', __FILE__ ));
	}
	if (function_exists('is_multisite') && is_multisite()) {
		add_action('network_admin_menu', 'bvWpeAdminMenu');
	} else {
		add_action('admin_menu', 'bvWpeAdminMenu');
	}
endif;

if ( !function_exists('bvSettingsLink') ) :
	function bvSettingsLink($links, $file) {
		global $blogvault, $bvWPEAdminPage;
		if ( $file == plugin_basename( dirname(__FILE__).'/blogvault.php' ) ) {
			$links[] = '<a href="'.$blogvault->bvAdminUrl($bvWPEAdminPage).'">'.__( 'Settings' ).'</a>';
		}
		return $links;
	}
	add_filter('plugin_action_links', 'bvSettingsLink', 10, 2);
endif;

if ( !function_exists('bvWpEMigrate') ) :
	function bvWpEMigrate() {
		global $blogvault, $bvNotice, $bvWPEAdminPage, $bvAppUrl;
		$_error = NULL;
		if (array_key_exists('error', $_REQUEST)) {
			$_error = $_REQUEST['error'];
		}
?>
<div class="wrap">
	<header>
	<a href="http://wpengine.com/"><img src="<?php echo plugins_url('assets/images/wpengine-logo.png', __FILE__); ?>" width="180px" /></a>
  <p class="poweredBy u-pull-right"><a href="http://blogvault.net"><img src="<?php echo plugins_url('assets/images/blogvault-logo-120.png', __FILE__); ?>" width="140px"/></a></p>
	</header>

	<hr/>
	<div class="row">

		<div class="seven columns">
			<form id="wpe_migrate_form" dummy=">" action="<?php echo $bvAppUrl; ?>/home/migrate" method="post" name="signup">
				<h1>Migrate My Site to WP Engine</h1>
				<p>The WP Engine Automated Migration plugin allows you to easily migrate your entire WordPress site from
					your previous hosting service to WP Engine for free.</p>
				<p>Take the information from the migration page of your <a href="http://my.wpengine.com">WP Engine User Portal</a>, and paste
				those values into the fields below, and click "Migrate".</p>
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
<?php echo $blogvault->siteInfoTags($bvWPEAdminPage); ?>
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
				<p style="font-size: 11px;">By pressing the "Migrate" button, you are agreeing to <a href="http://wpengine.com/terms-of-service/">WP Engine's Terms of Service</a></p>
					<?php submit_button("Migrate", "primary", "migrate-my-site", true); ?>
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
<?php
	}
endif;