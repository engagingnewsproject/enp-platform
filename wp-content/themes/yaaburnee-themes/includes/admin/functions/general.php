<?php
	global $different_themes_managment;
	$different_themes_managment = new DifferentThemesManagment(THEME_FULL_NAME, THEME_NAME);


	//load the files that contain the options
	$options_files=array('general', 'style', 'slider', 'sidebar', 'documentation');
	foreach($options_files as $file){
		get_template_part(THEME_ADMIN_INCLUDES.$file);
	}


	global $options;
	$options = $different_themes_managment->get_options();

	function theme_configuration() {
		
		global $themename, $themeslug, $options, $different_themes_managment;

		if ( isset ( $_REQUEST['saved'] ) && $_REQUEST['saved'] ) {
			$different_themes_managment->print_saved_message();		
		}
		if ( isset ( $_REQUEST['reset'] ) && $_REQUEST['reset'] ) {
			$different_themes_managment->print_reset_message();		
		}

		$different_themes_managment->print_heading("get more from Different Themes!");
		$different_themes_managment->print_options();
		$different_themes_managment->print_footer();
	}

	add_action('admin_menu', 'theme_menu');

	function theme_menu() {

		global $themename, $themeslug, $options;
		
		$nonsavable_types=array('navigation', 'tab','sub_navigation','sub_tab','homepage_set_test','save','closesubtab','closetab','row','close');

		//insert the default values if the fields are empty
		foreach ($options as $value) {
			if( isset( $value['id'] ) && get_option($value['id'])=='' && isset($value['std']) && !in_array($value['type'], $nonsavable_types)){
				update_option( $value['id'], $value['std']);
			}
		}

		//save the field's values if the Save action is present
		if ( isset( $_GET['page'] ) && $_GET['page'] == 'theme-configuration' ) {
			if ( isset( $_REQUEST['action'] ) && 'save' == $_REQUEST['action'] ) {
				//verify the nonce
				if ( empty($_POST) || !wp_verify_nonce($_POST['different-theme-options'],'different-theme-update-options') )
				{
				   print 'Sorry, your nonce did not verify.';
				   exit;
				}else{
					if(get_option('different_themes_first_save')==''){
						update_option('different_themes_first_save', 'saved');
					}
					foreach ($options as $value) {
						if(isset($value['id']) && isset($_REQUEST[$value['id']]) && !in_array($value['type'],$nonsavable_types)) {
							
							if($value['type']=="checkbox" && $_REQUEST[$value['id']]=="on"){
								update_option($value['id'],$_REQUEST[$value['id']]); 
							}
							if($value['type']=="aweber_input") {
								$arrayAweber = get_option(THEME_NAME."_aweber_keys");
								 
								if(empty($arrayAweber) || $_REQUEST[$value['id']] != get_option($value['id'])) {
									$oauth_id = $_REQUEST[$value['id']];
									
									if($oauth_id) {
										try {
											list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = AWeberAPI::getDataFromAweberID($oauth_id);
										} catch (AWeberAPIException $exc) {
											list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = null;
											# make error messages customer friendly.
											$descr = $exc->description;
											$descr = preg_replace('/http.*$/i', '', $descr);     # strip labs.aweber.com documentation url from error message
											$descr = preg_replace('/[\.\!:]+.*$/i', '', $descr); # strip anything following a . : or ! character
											$error_code = " ($descr)";
										} catch (AWeberOAuthDataMissing $exc) {
											list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = null;
										} catch (AWeberException $exc) {
											list($consumerKey, $consumerSecret, $accessKey, $accessSecret) = null;
										}
									}
									
									$keys = array(
										'consumer_key' => $consumerKey,
										'consumer_secret' => $consumerSecret,
										'access_key' => $accessKey,
										'access_secret' => $accessSecret,
									);
									
									update_option(THEME_NAME."_aweber_keys", $keys);
									update_option($value['id'], $_REQUEST[$value['id']]);
								}
	
							}
							
							if($value['type']!="checkbox" && $value['type']!="aweber_input") {
								update_option($value['id'],$_REQUEST[$value['id']]); 
							}
						} elseif($value['type']=="checkboxes") {
							foreach($value['checkboxes'] as $checkbox) {
								if($_REQUEST[$checkbox['id']]=="on"){
									update_option($checkbox['id'],$_REQUEST[$checkbox['id']]); 
								} else {
									update_option($checkbox['id'], "off"); 
								}
							}
						} elseif($value['type']=="homepage_blocks") {
							$fieldID = get_option(THEME_NAME."_homepage_layout_order");
							if ( is_array( $fieldID ) ) {
								$a=0;
								foreach($fieldID as $sssss) {
									if($fieldID[$a]['type'] == "homepage_skill") { 
										foreach($value['blocks'] as $block) {
											if($block['type'] == "homepage_skill") { 
												for($l=1; $l<=10; $l++) {
													foreach($block['options'] as $blockOption) {
														if($blockOption['type']=="add_input") {
															update_option($blockOption['id']."_n".$l."_text_".$fieldID[$a]['id'], $_REQUEST[$blockOption['id']."_n".$l."_text_".$fieldID[$a]['id']]); 
														} else {
															update_option($blockOption['id']."_".$fieldID[$a]['id'], $_REQUEST[$blockOption['id']."_".$fieldID[$a]['id']]); 
														}
													}
												}
											}
										}
									} else {
										foreach($value['blocks'] as $block) {
											foreach($block['options'] as $blockOption) {
												if(isset($_REQUEST[$blockOption['id']."_".$fieldID[$a]['id']])) {
													update_option($blockOption['id']."_".$fieldID[$a]['id'], $_REQUEST[$blockOption['id']."_".$fieldID[$a]['id']]); 
												}
											}
											
										}
									}
									$a++;
								}
							}
							
						}  elseif(!in_array($value['type'], $nonsavable_types) && isset($value['id'])){
							if($value['type']!="aweber_input") {
								delete_option( $value['id'] ); 
							}
						}

						if($value['type']=='add_text') {
							$old_val = $_REQUEST[ $value['id'].'s' ];
							$old_val = explode( "|*|", $old_val );
							
							if (!in_array($_REQUEST[ $value['id'] ], $old_val)) {
								update_option( $value['id'].'s', $_REQUEST[ $value['id'].'s' ].sanitize_title($_REQUEST[ $value['id'] ])."|*|" ); 
							}
							
						}
					}
					header("Location: admin.php?page=theme-configuration&saved=true");
					die;
				}		
			} 
		}

		add_menu_page(THEME_FULL_NAME.' Management', THEME_FULL_NAME.' Management', 'administrator', 'theme-configuration', 'theme_configuration',THEME_IMAGE_URL.'control-panel-images/logo-differentthemes-1.png');

	}

?>