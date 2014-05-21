<?php
/**
 * Author Different-Themes
 * http://wwww.different-themes.com
 */

get_template_part(THEME_ADMIN_INCLUDES."functions/default-values");


/* -------------------------------------------------------------------------*
 * 								THEME MANAGEMENT							*
 * -------------------------------------------------------------------------*/
 
class DifferentThemesManagment {

	var $options=array();
	var $before_item_title='<h2>';
	var $after_item_title='</h2>';
	var $before_item='<div class="row">';
	var $after_item='</div>';
 
	function DifferentThemesManagment($themename, $themeslug) {
		$this->themename=$themename;
		$this->themeslug=$themeslug;
	}
	
	
	function get_options() {
		return $this->options;
	}

	function add_options($option_arr) {
		foreach($option_arr as $option){
			$this->options[]=$option;
		}
	}

	function print_heading($heading_text) {
		$theme_data = wp_get_theme();
		echo '<!-- BEGIN .main-wrapper -->
			<div class="main-wrapper clearfix">
			<!-- BEGIN .header -->
			<div class="header">
				<p>'.THEME_FULL_NAME.' <span>v'.$theme_data->Version.'</span></p>
				<div>
					<a href="#"><img src="'.THEME_IMAGE_CPANEL_URL.'logo-ot-1.png" alt="Different Themes"/></a>
					<a href="http://www.themeforest.net/user/different-themes/portfolio?ref=different-themes" target="blank" class="more">'.$heading_text.'</a>
				</div>
			<!-- END .header -->
			</div>
			
			<form method="post" id="different-themes-options">';
		if ( function_exists('wp_nonce_field') ){
			wp_nonce_field('different-theme-update-options','different-theme-options');
		}
		echo '
		<div id="tabs">
		<!-- BEGIN .sidebar -->
		<div class="sidebar">
				<ul class="main-menu">';
					$i=0;
					foreach ($this->options as $value) {
						if($value['type']=='navigation'){
							echo '<li class="'.$value['slug'].'"><a href="#tabs-'.$i.'" class="config_tab">'.$value['name'].'</a></li>';
							$i++;
						}
					}
		echo '</ul>		
			<!-- END .sidebar -->
			</div>

			';
					
				
	}
	
	function print_footer() {
	
		echo '
		
		</div>
		</form>
		<!-- END .main-wrapper -->
		</div>
';
	}
	
	function print_options(){
		$i=0;
		foreach ($this->options as $value) {
			$this->print_options_switch($value['type'], $value);
		}
	}
	
	function print_options_switch($switchValue, $arrayValue) {
		global $i;
		switch ($switchValue) {
			case 'navigation':
				$i++;
			break;
			case 'tab':
				$this->print_tab($arrayValue, $i);
			break;
			case 'closetab':
				$this->print_closetab($arrayValue, $i);
			break;
			case 'sub_navigation':
				$this->print_subnavigation($arrayValue, $i);
			break;
			case 'sub_tab':
				$this->print_subtab($arrayValue, $i);
			break;
			case 'closesubtab':
				$this->print_closesubtab($arrayValue, $i);
			break;
			case 'homepage_set_test':
				$this->print_homepagesettest($arrayValue);
			break;
			case 'upload':
				$this->print_upload($arrayValue);
			break;
			case 'title':
				$this->print_title($arrayValue);
			break;
			case 'input':
				$this->print_input($arrayValue);
			break;
			case 'aweber_input':
				$this->print_aweber_input($arrayValue);
			break;
			case 'aweber_select':
				$this->print_aweber_select($arrayValue);
			break;
			case 'checkbox':
				$this->print_checkbox($arrayValue);
			break;
			case 'select':
				$this->print_select($arrayValue);
			break;
			case 'save':
				$this->print_save($arrayValue);
			break;
			case 'row':
				$this->print_row($arrayValue);
			break;
			case 'close':
				$this->print_close($arrayValue);
			break;
			case 'textarea':
				$this->print_textarea($arrayValue);
			break;
			case 'pages':
				$this->print_pages($arrayValue);
			break;
			case 'categories':
				$this->print_categories($arrayValue);
			break;
			case 'radio':
				$this->print_radio($arrayValue);
			break;
			case 'slide_order':
				$this->print_slide_order($arrayValue);
			break;
			case 'sidebar_order':
				$this->print_sidebar_order($arrayValue);
			break;
			case 'text':
				$this->print_text($arrayValue);
			break;
			case 'text_2':
				$this->print_text_2($arrayValue);
			break;
			case 'add_text':
				$this->print_add_text($arrayValue);
			break;
			case 'add_input':
				$this->print_add_input($arrayValue);
			break;
			case 'sidebar':
				$this->print_sidebar($arrayValue);
			break;
			case 'google_font_select':
				$this->print_google_font_select($arrayValue);
			break;
			case 'homepage_blocks':
				$this->print_homepage_blocks($arrayValue);
			break;
			case 'sidebar_select':
				$this->print_sidebar_select($arrayValue);
			break;
			case 'scroller':
				$this->print_scroller($arrayValue);
			break;
			case 'color':
				$this->print_color($arrayValue);
			break;
			case 'unique':
				$this->print_unique($arrayValue);
			break;
			case 'user':
				$this->print_user($arrayValue);
			break;
			case 'layer_slider_select':
				$this->print_layer_slider_select($arrayValue);
			break;
		}
	}

	
	function print_tab($value, $i){
		echo '

		<div id="tabs-'.($i-1).'" ><div class="content">';
	}		

	
	/**
	*	Prints a text.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "text",
	*		"text" => "Your text comes here",
	*	)
	*/
	function print_text($value){
		if ( isset ( $value['protected'][0]["id"] ) || isset ( $value['std'] ) ) {
			$protected_value = $this->get_field_value( $value['protected'][0]["id"], $value['std'] );
		}
		
		if ( isset ( $value['protected'][1]["id"] ) || isset ( $value['std'] ) ) {
			$protected_value_1 = $this->get_field_value( $value['protected'][1]["id"], $value['std'] );
		}
		
		if ( isset ( $value['id'] ) || isset ( $value['std'] ) ) {
			$input_value = $this->get_field_value( $value['id'], $value['std'] );
		}
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || (!isset($value['protected'][0]["id"]))) {

			
			echo '<div id="theme_documentation" style="min-height:300px;">
			<div style="margin-left:33px; margin-right:25px;">
			'.$value['text'].'	
			</div>								
			</div>';
		}
	}	
	
	/**
	*	Prints a text.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "text_2",
	*		"text" => "Your text comes here",
	*	)
	*/
	function print_text_2($value){
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {

			
			echo '<div id="theme_documentation" style="margin-bottom:10px;">
			<div style="margin-left:0px; margin-right:25px;">
			'.$value['text'].'	
			</div>								
			</div>';
		}
	}
	
	/**
	*	Prints a sub navigation.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "sub_navigation",
	*		"subname"=>array(
	*			array("slug"=>"documentation", "name"=>"Documentation")
	*		)
	*	)
	*/
	function print_subnavigation($value, $i){
		if($value['subname']){
			echo '<div class="sub_tabs">';
			echo '<div class="tabs clearfix">';
			echo '<ul>';
			foreach($value['subname'] as $subname){
				echo '<li><a href="#sub_tabs-'.($i-1).'-'.$subname['slug'].'" class="config_stab" >'.$subname['name'].'</a></li>';
			}
			echo '</ul></div>';
	 	}
	}
	
	function print_subtab($value, $i){
		echo '<div id="sub_tabs-'.($i-1).'-'.$value['slug'].'">';
	}
	
	/**
	*	Check if homepage is set.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "homepage_set_test",
	*		"title" => "Set up Your Homepage and post page!",
	*		"desc" => "	<p><b>You have not selected the correct template page for homepage.</b></p>
	*		<p>Please make sure, you choose template \"Homepage\".</p>
	*		<br/>
	*		<ul>
	*			<li>Current front page: <a href='".get_permalink(get_option('page_on_front'))."'>".get_the_title(get_option('page_on_front'))."</a></li>
	*			<li>Current blog page: <a href'".get_permalink(get_option('page_for_posts'))."'>".get_the_title(get_option('page_for_posts'))."</a></li>
	*		</ul>",
	*		"desc_2" => "<p><b>You have NOT enabled homepage.</b></p>
	*		<p>To use custom homepage, you must first create two <a href='".home_url()."/wp-admin/post-new.php?post_type=page'>new pages</a>, and one of them assign to \"<b>Homepage</b>\" template.Give each page a title, but avoid adding any text.</p>
	*		<p>Then enable homepage  in <a href='".home_url()."/wp-admin/options-reading.php'>wordpress reading settings</a> (See \"Front page displays\" option). Select your previously created pages from both dropdowns and save changes.</p>"
	*	)
	*/
	function print_homepagesettest($value){
		echo $this->before_item.$this->before_item_title.$value['title'].$this->after_item_title;
						$homepage = get_option('show_on_front');
						if(get_option( 'page_on_front')) {
							$meta = get_post_custom_values("_wp_page_template",get_option( 'page_on_front'));
						}
						if($homepage == "page" && ($meta[0] == "template-homepage.php" || strpos($meta[0],"template-homepage.php" !== false ) )) { global $has_homepage; $has_homepage=true; } else { $has_homepage=false; }
						
						if($has_homepage) {
							echo '<ul style="margin:0 0 0 33px;">';
										if(get_option("page_on_front")) echo '<li>Front page: <a href="'.get_permalink(get_option("page_on_front")).'">'.get_the_title(get_option("page_on_front")).'</a></li>';
										if(get_option("page_for_posts")) echo '<li>Blog page: <a href="'.get_permalink(get_option("page_for_posts")).'">'.get_the_title(get_option("page_for_posts")).'</a></li>';
								echo '</ul>';						
						} elseif ($homepage == "page") {
							echo '<div id="theme_documentation">
									<div style="margin-left:33px; margin-right:25px;">
											'.$value['desc'].'
										</div>
									</div>';
						} else {
							echo '<div id="theme_documentation">
										<div style="margin-left:33px; margin-right:25px;">
											'.$value['desc_2'].'								
										</div>
									</div>';
						}
						echo '
							
						'
						.$this->after_item;
	}	
	/**
	*	Check if homepage is set.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*	"type" => "homepage_blocks",
	*	"title" => "Homepage Blocks:",
	*	"id" => $different_themes_managment->themeslug."_homepage_blocks",
	*	"blocks" => array(
	*		array(
	*			"title" => "Quote Text",
	*			"type" => "homepage_quote_text",
	*			"options" => array(
	*				array( "type" => "textarea", "id" => $different_themes_managment->themeslug."_homepage_quote_text", "sub_title" => "Text:" ),
	*			),
	*		),
	*		array(
	*			"title" => "Main Article News Block",
	*			"type" => "homepage_main_news_block",
	*			"options" => array(
	*				array(
	*					"type" => "categories",
	*					"id" => $different_themes_managment->themeslug."_homepage_main_news_block_post",
	*					"taxonomy" => "category",
	*					"title" => "Set News Category"
	*				),
	*				array(
	*					"type" => "categories",
	*					"id" => $different_themes_managment->themeslug."_homepage_main_news_block_gal",
	*					"taxonomy" => "gallery-cat",
	*					"title" => "Set Gallery Category"
	*				),
	*	
	*			),
	*		)
	*	)
	*
	*/
	function print_homepage_blocks($value){
		global $post_id;
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = get_option(THEME_NAME."_homepage_layout_order_".$post_id);
			echo $this->before_item.$this->before_item_title.$value['title'].$this->after_item_title.'
					<ul class="blocks block-available clearfix inactive-blocks" id="available-homepage-blocks">';
						foreach ($value['blocks'] as $block) {
						echo '
						<li class="inactive-block component" rel="'.$block['type'].'">
							<div class="blocks-content clearfix">'.$block['title'].'<a href="javascript:{}" class="button move">'.__("Move", THEME_NAME).'</a>
								<div style="display:none;">
								';
										foreach($block['options'] as $blockOption) {
											$this->print_options_switch($blockOption["type"],$blockOption);
										}
								echo '
								</div>
							</div>
						</li>
						';
							
						}
				echo'
					</ul>

					<div class="message-1">
						To add content to your Hompage, drag &amp; drop blocks from Available to Active area 
					</div>

					<h2>Active homepage blocks</h2>
						<ul class="settings ui-layout-center blocks block-active clearfix ui-droppable" style="min-height:100px;" id="active-homepage-blocks">';
							if($input_value) {
								foreach ($input_value as $block) {
									foreach($this->options as $v) {
										if( $v['type'] == "homepage_blocks" ) {
											foreach($v['blocks'] as $vv) {
												if( $vv['type'] == $block['type'] ) {
													$optTitle = $vv['title'];
												}
											}
										}
									}
									
									echo '
										<li class="active-block component dropped" rel="'.$block['type'].'" id="recordsArray_'.$block['id'].'" style="opacity: 1; z-index: 0; ">
											<div class="blocks-content clearfix">'.$optTitle.'<a href="javascript:{}" id="edit_'.$block['id'].'" class="button edit">Edit</a><a href="javascript:{}" id="delete_'.$block['id'].'"  class="button delete del">Delete</a>
												<div></div>
												<div style="display:none;">
													'; 
															foreach($this->options as $v) {
																if( $v['type'] == "homepage_blocks" ) {
																	foreach($v['blocks'] as $vv) {
																		if( $vv['type'] == $block['type'] ) {
																			$n=0;
																			foreach($vv['options'] as $vvv) {
																				$vv['options'][$n]['id'] = $vvv['id'].'_'.$block['id'];
																				$opt = $vvv['type'];
																				$optType =$vv['options'][$n];
																				$this->print_options_switch($opt,$optType);	
																			$n++;
																			}
																		}
																	}
																}
															}
													echo '
												</div>
											</div>
										</li>
											';
											
											}
										}
								echo'
									</ul>
	';
				echo ''.$this->after_item;
				echo '<input type="submit" value="Save" id="df-submit-home" class="button button-primary button-large" style="float:right;"/>	';
		}
	}
	
	function print_unique($value){
		echo '<div id="unique-block" style="display:none;">&nbsp;</div>';
	}

	/**
	*	Prints a aweber_input field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "aweber_input",
	*		"title" => "Twitter Account Url:",
	*		"id" => $different_themes_managment->themeslug."_twitter",
	*		//if needed you can set a protection	
	*		"protected" => array(
	*			array("id" => $different_themes_managment->themeslug."_social_footer", "value" => "on")
	*		)
	*	)
	*/
	function print_aweber_input($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
		?>
		<?php if(isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
			<span class="input-text"><input type="text" name="<?php echo $value['id'];?>" value="<?php echo $input_value;?>" <?php if(isset($value['number']) && $value['number'] == "yes") { ?>style="width: 46px;"<?php } ?>/></span>
		</div>
		<?php
		}
	}	
	/**
	*	Prints a aweber_select.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "aweber_input",
	*		"title" => "Twitter Account Url:",
	*		"id" => $different_themes_managment->themeslug."_twitter",
	*		//if needed you can set a protection	
	*		"protected" => array(
	*			array("id" => $different_themes_managment->themeslug."_social_footer", "value" => "on")
	*		)
	*	)
	*/
	function print_aweber_select($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
			
		?>

		<?php
			if (isset($value['info'])) {
				echo different_themes_info_message($value['info']);
			}
			$keys = get_option(THEME_NAME."_aweber_keys");
			
			
			if ($keys['access_key']) {
				extract($keys);
				$error_ = null;
				try {
					$aweber = new AWeberAPI($consumer_key, $consumer_secret);
					$account = $aweber->getAccount($access_key, $access_secret);
				} catch (AWeberException $e) {
					$error_ = get_class($e);
					$account = null;
				}
				if (!$account) {
					if($error_ != 'AWeberOAuthException' && $error_ != 'AWeberOAuthDataMissing') {
					echo "Unable to connect to AWeber's API.  Please refresh the page, or attempt to reauthorize.";
					$temp_error = True;
					} else {
						$this->deauthorize();
						 echo "AWeber Web Form authentication failed";
					}
				} else {
					$authorize_success = True;
					$button_value = 'Remove Connection';
				}
			}
            $lists = $account->lists;
            if (!empty($lists)) {
		?>
		<?php if(isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
			<span class="select">
				<select name="<?php echo $value['id'];?>">
					<option value="FALSE"><?php printf ( __( 'Select A List' , THEME_NAME )); ?></option>
					<?php foreach ($lists as $this_list): ?>
						<option value="<?php echo $this_list->id; ?>"<?php echo ($this_list->id == $input_value) ? ' selected="selected"' : ""; ?>><?php echo $this_list->name; ?></option>
					<?php endforeach; ?>
				</select>
			</span>
		</div>
		  <?php } else { ?>
			<div id="theme_documentation" style="min-height:30px;">
				<div style="margin-left:33px; margin-right:25px;">
					<?php printf ( __( 'This AWeber account doesn\'t currently have any lists or your authorization code is incorrect!' , THEME_NAME )); ?>
				</div>
			</div>
        <?php } ?>
      <?php

		}
	}	
	/**
	*	Prints a upload field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "upload",
	*		"title" => "Add logo image",
	*		"info" => "Suggested image size is 166x28px",
	*		"id" => $different_themes_managment->themeslug."_logo"
	*	)
	*/
	function print_upload($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
		

		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
				<label><?php echo $value['title'];?></label>
		<?php
			if (isset($value['info'])) {
				echo different_themes_info_message($value['info']);
			}	
		?>
				<span>
					<div class="uploader">
						<input type="text" name="<?php echo $value['id'];?>" value="<?php echo $input_value;?>" class="upload df-upload-field" style="width:199px;  <?php if( $value['home'] == "yes" ) { ?>margin-left:483px;<?php } ?>" id="<?php echo $value['id'];?>" />
						<span id="<?php echo $value['id'];?>_button" class="action btn-upload df-upload-button" style="width:100px; float:right; z-index:500;"><?php _e("Choose File", THEME_NAME);?></span>
					</div>
				</span>
			</div>
		<?php 
	
		}
	}			
	
	/**
	*	Prints a input field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "input",
	*		"title" => "Twitter Account Url:",
	*		"id" => $different_themes_managment->themeslug."_twitter",
	*		//if needed you can set a protection	
	*		"protected" => array(
	*			array("id" => $different_themes_managment->themeslug."_social_footer", "value" => "on")
	*		)
	*	)
	*/
	function print_input($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);

		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
			<label>
				<?php 
					echo $value['title'];
				?>
			</label>
				<?php
					if (isset($value['info'])) {
						echo different_themes_info_message($value['info']);
					}	
				?>
			
			<span class="input-text"><input type="text" name="<?php echo $value['id'];?>" value="<?php echo $input_value;?>" <?php if(isset($value['number']) && $value['number'] == "yes") { ?>style="width: 46px;"<?php } ?>/></span>
		</div>
		<?php
		}
	}		
	/**
	*	Prints a color field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array( "type" => "color", "id" => $different_themes_managment->themeslug."_homepage_slogans_color", "title" => "Text Color:", "std" => "8f0029" ),
	*/
	function print_color($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
		<div class="input-item-full-width clearfix">
		<?php } ?>
			<label style="width:158px;"><?php echo $value['title'];?></label>
			<span class="input-text">
				<input type="value" class="color" name="<?php echo $value['id'];?>" value="<?php echo $input_value;?>" style="width: 300px; border: #D1D1D1 1px solid; border-radius: 3px; outline: 0; font: 12px Arial, sans-serif; color: #333; padding: 7px 10px;"/>
			</span>
		
		</div>
		<?php
		}
	}			
	
	/**
	*	Prints a checkbox.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "checkbox",
	*		"title" => "Show Social Networks Icons In Header",
	*		"id" => $different_themes_managment->themeslug."_social_header",
	*		//if needed you can set a protection	
	*		"protected" => array(
	*			array("id" => $different_themes_managment->themeslug."_social_footer", "value" => "on")
	*		),
	*		
	*	)
	*/
	function print_checkbox($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);

			if($input_value == "on") {
				$checked='checked="yes"'; 
			} else { 
				$checked=''; 
			}
			if (isset($value['info'])) {
				echo different_themes_info_message($value['info']);
			}	
		?>	
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix" ><?php } else { ?>
		<div class="input-item-half-width clearfix">
		<?php } ?>
			<label><?php echo $value['title'];?></label>	
			<span class="select"><input type="checkbox" name="<?php echo $value['id'];?>" id="<?php echo $value['id'];?>"  value="on" <?php echo $checked;?>/></span>			
		</div>		
		<?php	
		}		
	}		
	
	
	/**
	*	Prints a select box.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "select",
	*		"title" => "Blog List Style",
	*		"id" => $different_themes_managment->$different_themes_managment->themeslug."_news_style",
	*		"options"=>array(
	*			array("slug"=>"style_1", "name"=>"Style 1 (News and posts with big images)"), 
	*			array("slug"=>"style_2", "name"=>"Style 2 (News and posts with small images)"),
	*			),
	*		"std" => "style_1"
	*	)
	*/
	function print_select($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
			
		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix <?php if(isset($value["class"])) { echo $value["class"]; }?>"><?php } else { ?>
			<div class="input-item-full-width clearfix <?php echo $value['class'];?>">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
		<?php
			if (isset($value['info'])) {
				echo different_themes_info_message($value['info']);
			}
		?>
			<span class="select">
				<select name="<?php echo $value['id'];?>">
					<?php
					foreach($value['options'] as $options) {
						if($input_value == $options['slug']) { $selected='selected="selected"'; } else { $selected=''; }
						echo '<option value="'.$options['slug'].'" '.$selected.'>'.$options['name'].'</option>';
					}
					?>
				</select>
			</span>
		</div>							
		<?php
	

		}
	}	
	
	/**
	*	Prints a layer_slider_select box.
	*/
	function print_layer_slider_select($value) {
	
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
			
			// Get WPDB Object
			global $wpdb;

			// Table name
			$table_name = $wpdb->prefix . "layerslider";
			
			// Get sliders
			$sliders = $wpdb->get_results( "SELECT * FROM $table_name
												WHERE flag_hidden = '0' AND flag_deleted = '0'
												ORDER BY id ASC LIMIT 200" );
			
		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix <?php echo $value['class'];?>"><?php } else { ?>
			<div class="input-item-full-width clearfix <?php echo $value['class'];?>">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
		<?php
			if (isset($value['info'])) {
				echo different_themes_info_message($value['info']);
			}
		?>
			<span class="select">
				<select name="<?php echo $value['id'];?>">
				<?php if(!empty($sliders)) : ?>
				<?php foreach($sliders as $key => $item) : ?>
				<?php $name = empty($item->name) ? 'Unnamed' : $item->name; ?>
				<?php if($input_value == $item->id) { $selected='selected="selected"'; } else { $selected=''; } ?>
					<option value="<?php echo $item->id; ?>" <?php echo $selected;?>><?php echo $name; ?></option>
				<?php endforeach; ?>
				<?php endif; ?>
				<?php if(empty($sliders)) : ?>
					<option value=""><?php _e("You didn't create a slider yet.", THEME_NAME); ?></option>
				<?php endif; ?>
				
				</select>
			</span>
		</div>							
		<?php
	

		}
	}
	
	/**
	*	Prints a select box.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "google_font_select",
	*		"title" => "Google Fonts",
	*		"id" => $different_themes_managment->$different_themes_managment->themeslug."__font",
	*		"sort" => "alpha",
	*		"default_font" => "Pacifico"
	*	)
	*/
	function print_google_font_select($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);

			$default_font = $value['default_font'];
			if($default_font) {
				$google_list = OT_get_google_fonts($value['sort']);
				if($google_list!=false) {
					$font_list = array_merge(array($default_font),$google_list);
				}
			} else {
				$google_list = OT_get_google_fonts($value['sort']);
				$font_list = OT_get_google_fonts($value['sort']);
			}

			$options = get_transient('df_font_options_'.$input_value);
			if($google_list!=false && $options==false) {
				foreach($font_list as $key => $font) {
					if($input_value == $font) { $selected='selected="selected"'; } else { $selected=''; }
					if($key==0) {
						$options.= '<option value="'.$font['font'].'">'.$font['font'].' '.$font['txt'].'</option>';
					} else {
						$options.= '<option value="'.$font.'" '.$selected.'>'.$font.'</option>';
					}
				}
				set_transient( 'df_font_options_'.$input_value, $options, 3600 );
			} else {
				$options.= '<option value="">Something is Wrong with your server configuration.</option>';
			}
		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
		<?php
			if ($value['info']) {
				echo different_themes_info_message($value['info']);
			}
		?>
			<span class="select">
				<select name="<?php echo $value['id'];?>">
					<?php echo $options;?>
				</select>
			</span>
		</div>	
		<?php
		}
	}
	
	/**
	*	Prints a textarea.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "textarea",
	*		"title" => "Homepage text block",
	*		"sub_title" => "Text:",
	*		"id" => $different_themes_managment->themeslug."_homepage_text_bock_txt",
	*		//if needed you can set a protection
	*		"protected" => array(
	*			array("id" => $different_themes_managment->themeslug."_homepage_text_blocks_enabled", "value" => "on")
	*		)
	*	),
	*/
	function print_textarea($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
				<label><?php echo $value['title'];?></label>
				<?php 
					if (isset($value['info'])) {
						echo different_themes_info_message($value['info']);
					}
				?>
				<span class="textarea"><textarea name="<?php echo $value['id'];?>"><?php echo $input_value;?></textarea></span>
				<?php if(isset($value['sample'])) { ?>
					<label style="font-size:10px;"><strong>Sample:</strong> <?php echo remove_html_slashes($value['sample']);?></label>
				<?php } ?>
			</div>
		<?php
		}
	}
	
	/**
	*	Prints pages in a select box.
	*/
	function print_pages($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
		?>

		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix <?php echo $value["class"];?>"><?php } else { ?>
			<div class="input-item-full-width clearfix <?php echo $value["class"];?>">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
			<span class="select">
				<select name="<?php echo $value["id"];?>" class="styled">
					<option value=""><?php _e("Select a category name", THEME_NAME);?></option>
			<?php 
				$pages = get_pages(); 
				foreach($pages as $pagg) {
					if($input_value==$pagg->ID) { $selected=' selected'; } else { $selected=''; }
					echo "<option value=\"".$pagg->ID."\" ".$selected.">".$pagg->post_title."</option>";
				}
			?>
				</select>
			</span>
		</div>	
		<?php
		}
		
	}	
	
	/**
	*	Prints categories in a select box.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "categories",
	*		"id" => $different_themes_managment->themeslug."_homepage_main_news_block_post",
	*		"taxonomy" => "category",
	*		"title" => "Set News Category"
	*	),
	*/	
	function print_categories($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);

			global $wpdb;
			$data = get_terms( $value["taxonomy"], 'hide_empty=0' );	
		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix <?php if(isset($value["class"])) { echo $value["class"]; }?>"><?php } else { ?>
			<div class="input-item-full-width clearfix <?php echo $value["class"];?>">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
		<?php if(count($data) > 0) {?>
			<span class="select">
				<select name="<?php echo $value["id"];?>" class="styled">
					<option value=""><?php _e("Latest News", THEME_NAME);?></option>
			<?php 
				foreach($data as $d) {
					if($input_value==$d->term_id) { $selected=' selected'; } else { $selected=''; }
					echo "<option value=\"".$d->term_id."\" ".$selected.">".$d->name."</option>";
				}
			?>
				</select>
			</span>
		<?php } else { ?>
			<span class="select">
				<select name="<?php echo $value["id"];?>" class="styled">
					<option value=""><?php _e("Please create a category", THEME_NAME);?></option>
				</select>
			</span>
		<?php } ?>
		</div>
		<?php

		}
		
	}	
	/**
	*	Prints categories in a select box.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "user",
	*		"id" => $different_themes_managment->themeslug."_homepage_main_news_block_post",
	*		"title" => "Registered Users"
	*	),
	*/	
	function print_user($value) {
	
		$protected_value = $this->get_field_value($value['protected'][0]["id"], $value['std']);
		$protected_value_1 = $this->get_field_value($value['protected'][1]["id"], $value['std']);
		$input_value = $this->get_field_value($value['id'], $value['std']);
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || (!isset($value['protected'][0]["id"]))) {
			global $wpdb;
			$order = 'user_nicename';
			$users = $wpdb->get_results("SELECT * FROM $wpdb->users ORDER BY $order");
		?>
		<?php if( $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
			<span class="select">
				<select name="<?php echo $value["id"];?>" class="styled">
					<option value="">Select a user</option>
			<?php 
				foreach($users as $user) :
					if($input_value==$user->ID) { $selected=' selected'; } else { $selected=''; }
					echo "<option value=\"".$user->ID."\" ".$selected.">".$user->user_nicename."</option>";
				endforeach;
			?>
				</select>
			</span>
		</div>
		<?php

		}
		
	}
	
	/**
	*	Prints radio buttons.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "radio",
	*		"title" => "Custom Homepage slider",
	*		"id" => $different_themes_managment->themeslug."_homepage_slider",
	*		"radio" => array(
	*			array("title" => "Enable slider:", "value" => "on"),
	*			array("title" => "Use single image:", "value" => "single"),
	*			array("title" => "Disable slider:", "value" => "off")
	*		),
	*		"std" => "off"
	*	)
	*/
	function print_radio($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			$input_value = $this->get_field_value($value['id'], $default);
		
			$n=0;
			foreach($value["radio"] as $radio) {
				$n++;
	

				if($input_value == $radio["value"]) {
					$checked='checked="yes"'; 
				} else { 
					$checked=''; 
				}
		?>
			<div class="input-item-half-width clearfix">
				<label><?php echo $radio["title"];?></label>
				<span><input type="radio"  name="<?php echo $value["id"];?>" id="<?php echo $value["id"].'_'.$n;?>"  value="<?php echo $radio["value"];?>" <?php echo $checked;?>/></span>
			</div>	
		<?php
			}
		}		
	}
	
	/**
	*	Sidebar Order
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "sidebar_order",
	*		"title" => "Order Sidebars",
	*		"id" => THEME_NAME."_sidebar_name"
	*	),	
	*/
	function print_sidebar_order( $value ) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		if(((isset($protectedValue) && $protectedValue!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || ($protectedValue==false)) {

			$input_value = $this->get_field_value($value['id'], $default);

					
			$saved_value = get_option( $value['id'].'s' );
			$saved_value = explode( "|*|", $saved_value );
		
		?>
				
		<ul class="blocks block-active clearfix" id="sidebar_order">
			<?php
			$i=0;
			foreach ( $saved_value as $sidebar ) {
				if ( $sidebar != "" ) {
					$i++;			
			?>
					<li class="row-item-full-width clearfix" id="recordsArray_<?php echo convert_to_class($sidebar); ?>" alt="<?php echo $sidebar; ?>">
						<div class="blocks-content clearfix" style="text-align: left;">
							Sidebar name: <b><?php echo $sidebar; ?></b><a href="javascript:{}" class="button edit sidebar-edit" id="edit-<?php echo convert_to_class($sidebar); ?>" rel="<?php echo $sidebar; ?>">Edit</a><a href="javascript:{}" class="button delete sidebar-delete" id="delete-<?php echo convert_to_class($sidebar); ?>">Delete</a>
						</div>
					</li>
			<?php
				}	
			}			
			?>
		</ul>	
		
		<?php
		}
	}
	
	/**
	*	Prints a Add New Sidebar field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "add_text",
	*		"title" => "Add New Sidebar:",
	*		"id" => THEME_NAME."_sidebar_name"
	*		)
	*	)
	*/
	function print_add_text($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		if(((isset($protectedValue) && $protectedValue!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || ($protectedValue==false)) {

			$input_value = $this->get_field_value($value['id'], $default);

		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
			<div class="input-item-full-width clearfix">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
			<span class="input-text"><input type="text" name="<?php echo $value['id']; ?>" /></span>
		<?php
		}
		$saved_value = get_option( $value['id'].'s' );
		$saved_value = stripslashes($saved_value);

		echo '<input type="hidden" name="'.$value['id'].'s" id="'.$value['id'].'s" value="'.$saved_value.'" />';
		?>
		</div>
		<input type="hidden" name="action" value="save" />
		<a href="javascript:{}" onclick="document.getElementById('different-themes-options').submit(); return false;" class="button-1">Add</a>
			
		<?php
	}	
	/**
	*	Prints a unlimited input fields.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "add_input",
	*		"title" => "Add New Sidebar:",
	*		"id" => THEME_NAME."_sidebar_name"
	*		)
	*	)
	*/
	function print_add_input($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		$rel = 1;
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($protectedValue) && $protectedValue!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || ($protectedValue==false)) {

			for($a=1; $a<=5; $a++) {
				if(!isset($value["id_clean"])) { $valueId = $value["id"]; } else { $valueId = $value["id_clean"]; } 
				$input_value_text = get_option($valueId."_n".$a."_text".$value["id_key"]);
				if($input_value_text || $a==1 ) {
		?>
			<div class="input-item-full-width-inside clearfix">
				<label><?php echo $value['title'];?></label>
				<span class="input-text"><input type="text" name="<?php echo $valueId.'_n'.$rel.'_text'.$value["id_key"]; ?>" value="<?php echo $input_value_text;?>"/></span>
				<a href="javascript:void(0);" class="delete-tab-box" rel="<?php echo ($rel-1);?>">
					<img src="<?php echo THEME_IMAGE_CPANEL_URL;?>btn-cross-1.png" style="float:right; cursor:pointer; margin-top:2px;" />
				</a>
			</div>
		<?php
					$rel++;
				}
			} 
		?>
			<a href="javascript:void(0);" class="addtabbox" rel="<?php echo ($rel-1);?>">
				<img src="<?php echo THEME_IMAGE_CPANEL_URL;?>btn-plus-1.png" style="float:right; cursor:pointer; margin-top:10px;" />
			</a>

		<?php
		}	
	}	
	
	/**
	*	Prints a sidebar select field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "sidebar_select",
	*		"title" => "Sidebar:",
	*		"id" => THEME_NAME."_sidebar_id"
	*		)
	*	)
	*/
	function print_sidebar_select($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		if(((isset($protectedValue) && $protectedValue!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || ($protectedValue==false)) {

			$input_value = $this->get_field_value($value['id'], $default);

			$sidebar_names = get_option( THEME_NAME."_sidebar_names" );
			$sidebar_names = explode( "|*|", $sidebar_names );
		
		?>
		<?php if( isset($value['home']) && $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } else { ?>
		<div class="input-item-full-width clearfix">
		<?php } ?>
			<label><?php echo $value['title'];?></label>
			<span class="select">
				<select name="<?php echo $value["id"];?>" class="styled">
					<option value="">Default</option>
			<?php
				foreach ($sidebar_names as $sidebar_name) {
					if ( $input_value == $sidebar_name ) {
						$selected="selected=\"selected\"";
					} else { 
						$selected="";
					}
							
					if ( $sidebar_name != "" ) {
			?>
					<option value="<?php echo $sidebar_name;?>" <?php echo $selected;?>><?php echo $sidebar_name;?></option>
			<?php
					}
				}
			?>
				</select>
			</span>
		<?php
			$saved_value = get_option( $value['id'].'s' );
			$saved_value = stripslashes($saved_value);
			echo '<input type="hidden" name="'.$value['id'].'s" id="'.$value['id'].'s" value="'.$saved_value.'" />';
		?>
		</div>
		<?php
		}

		
	}
	
	/**
	*	Prints a sidebar field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array(
	*		"type" => "sidebar",
	*		"title" => "Twitter Account Url:",
	*		"id" => $different_themes_managment->themeslug."_twitter",
	*		//if needed you can set a protection	
	*		"protected" => array(
	*			array("id" => $different_themes_managment->themeslug."_social_footer", "value" => "on")
	*		)
	*	)
	*/
	function print_sidebar($value) {
		$protected_value = $this->get_field_value($value['protected'][0]["id"], "");
		$protected_value_1 = $this->get_field_value($value['protected'][1]["id"], "");
		$input_value = $this->get_field_value($value['id'], $value['std']);
	
		$saved_value=get_option( $value['id'].'s' );
		$saved_value=stripslashes($saved_value);
		$saved_value=explode('|*|', $saved_value);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || (!isset($value['protected'][0]["id"]))) {
			echo $this->before_item.$this->before_item_title.$value['title'].'</span>
					';
					foreach ( $saved_value as $sidebar_name) {
						echo $sidebar_name."<br/>";
					}
					echo ''
			.$this->after_item;
		}

	}
	
	/**
	*	Prints a input field.
	*
	*	EXAMPLE:
	*	-----------------------------------------------------------------
	*	array( "type" => "scroller", "id" => $different_themes_managment->themeslug."_homepage_section_title_size", "title" => "Font Size:", "max" => "250" ),
	*/
	function print_scroller($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		if(((isset($protectedValue) && $protectedValue!="") && $protected_value==$value['protected'][0]["value"]) && $protected_value_1==$value['protected'][1]["value"] || ($protectedValue==false)) {


		$input_value = $this->get_field_value($value['id'], $default);
		$input_value = (!$input_value ? 10 : $input_value);
		
	?>
		<?php if( $value['home'] == "yes" ) { ?><div class="input-item-full-width-inside clearfix"><?php } ?>
			<label><?php echo $value['title'];?></label>

			<script type="text/javascript">
				jQuery(document).ready(function($){
					jQuery( ".<?php echo $value['id'];?> > #slider-range-min-<?php echo $value['id'];?>" ).slider({
						range: "min",
						value: <?php echo $input_value;?>,
						min: 1,
						max: <?php echo $value['max'];?>,
						slide: function( event, ui ) {
							jQuery(this).prev("input").val(ui.value);
						}
					});
					jQuery(this).prev("input").val(jQuery(this).attr( "value" ) );		
				});
			</script>
			<span class="<?php echo $value['id'];?>" id="scroller">
				<input name="<?php echo $value['id'];?>" value="<?php echo $input_value;?>"  type="text" id="amount_<?php echo $value['id'];?>" style="border:0; color:#f6931f; font-weight:bold; width: 50px;" readonly="readonly"/>
				<div id="slider-range-min-<?php echo $value['id'];?>" class="slider-range-min"></div>
			</span>
		<?php if( $value['home'] == "yes" ) { ?></div><?php } ?>
		<?php 
		}

	}
	function print_save($value) {
	?>
		<div class="row">
			<input type="hidden" name="action" value="save" /><a href="javascript:{}" onclick="document.getElementById('different-themes-options').submit(); return false;" class="button-1"><?php echo $value['title'];?></a>
		</div>
	<?php
	}
	
	function print_title ( $value ) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			if(isset($value['home']) && $value['home']=="yes") {
				echo '<div class="input-item-full-width-inside clearfix" style="background:none!important; padding-top:15px!important; margin-top:15px!important;">'.$this->before_item_title.$value["title"].$this->after_item_title.'</div>';
			} else {
				echo $this->before_item_title.$value["title"].$this->after_item_title;
			}
		}
	}
	
	function print_slide_order ( $value ) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {
			
			if ( get_option(THEME_NAME."-slide-order-set") != "1" ) {
				$order = "";
			} else {
				$order = "&orderby=menu_order&order=ASC";
			}	
			
			if ( isset($value['count']) ) {
				$count = "&showposts=".$value['count'];
			} else {
				$count = false;
			}
	
			$cat = get_option($value['cat']);
			
			$my_query = new WP_Query("posts_per_page=20&cat=".$cat.$count.$order);
			?>
			<ul class="blocks block-active slider-sequence clearfix">
				<?php
				if ( $my_query->have_posts() ) : while ( $my_query->have_posts() ) : $my_query->the_post();
					global $post; 
					$thePostID = $post->ID;
					$image = get_post_thumb($thePostID,45,45,THEME_NAME."_slider_image"); 			
				?>
					<li class="row-item-full-width clearfix" id="recordsArray_<?php echo $post->ID; ?>">
						<div class="blocks-content clearfix">
							<div class="image"><img src="<?php echo $image['src']; ?>" alt="<?php get_the_title(); ?>" /></div>
							<div class="text-content">
								<p><b><?php echo get_the_title(); ?></b></p>
								<p><?php echo get_the_excerpt(); ?></p>
							</div>
						</div>
					</li>
				<?php
				endwhile; else: 
				endif;				
				?>
			</ul>
			<?php
		}
	}

	
	function print_row($value) {
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {

			echo '<div class="row">';
		
		}
	}
		
	function print_closesubtab ( $value, $i ) {
		echo '</div>';
	}
	
	function print_closetab ( $value, $i ) {
		echo '</div></div></div>';
	}	
	
	function print_close($value){
		if(isset($value['std'])) {
			$default = $value['std'];
		} else {
			$default = false;
		}
		if(isset($value['protected'][0]["id"])) {
			$protectedValue = $value['protected'][0]["id"];
		} else {
			$protectedValue = false;
		}
		
		$protected_value = $this->get_field_value($protectedValue, $default);
		
		
		if(((isset($value['protected'][0]["id"]) && $value['protected'][0]["id"]!="") && $protected_value==$value['protected'][0]["value"]) || (!isset($value['protected'][0]["id"]))) {

			echo '</div>';
		
		}
	}
	
	function get_field_value($id, $std){
		if ( get_option( $id ) != "") { 
			return stripslashes(get_option( $id )); 
		} else { 
			return stripslashes($std); 
		}
	}
	
	
	function print_saved_message(){
		//echo '<div class="note_box" id="saved_box">'.$this->themename.' settings saved.</div>';	
	}
		
}

	get_template_part(THEME_ADMIN_INCLUDES."functions/jquery-css-include");
	get_template_part(THEME_ADMIN_INCLUDES."functions/general");


?>