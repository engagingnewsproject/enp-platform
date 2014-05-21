<?php
/*
Plugin Name: WP-Cuf&oacute;n
Plugin URI: http://www.tobias-battenberg.de/code/wp-cufon
Description: Enables Cuf&oacute;n font-replacement in WordPress - After installation you only have to put your generated font-files to <em>/wp-content/plugins/fonts/</em> directory. Go to the <a href='themes.php?page=wp-cufon.php'>Settings Page</a> for more instructions and to add your own replacement scripts.
Version: 1.6.10
Author: Tobias Battenberg
Author URI: http://www.tobias-battenberg.de
*/
?>
<?php


// Backwards compatibility (WP pre 2.6) shim for URL and directory constants
if ( ! defined( 'WP_CONTENT_URL' ) )
      define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
if ( ! defined( 'WP_CONTENT_DIR' ) )
      define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
if ( ! defined( 'WP_PLUGIN_URL' ) )
      define( 'WP_PLUGIN_URL', WP_CONTENT_URL. '/plugins' );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
      define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );


function wpcufon_jquery_init() {
    // jQuery Enabler
	if(get_option("cufon_jquery") == "1"){
    	wp_enqueue_script( 'jquery' );
    }            
}    
 
add_action('init', 'wpcufon_jquery_init' );



function wpcufon_init() {	
?>    
    <!-- WP-Cufon Plugin 1.6.10 START  -->
    <script type="text/javascript" src="<?php echo WP_PLUGIN_URL; ?>/wp-cufon/js/cufon-yui.js"></script>
            
    <?php   
		// Collect names of fonts being used
		$fontStrArr = array();

		// Seach for fontFamily: 'xxx' 
		preg_match_all('/fontFamily:\s*\'([a-zA-Z0-9\s\-]+)\'/', get_option("cufon_replacement_script"), $arr);
		foreach($arr[1] as $f){
			$fontStrArr[] = str_replace(array("'"," "),array("","_"), $f);
		}

		// Search for 'fontFamily', 'xxx'
		preg_match_all('/\'fontFamily\',\s*\'([a-zA-Z0-9\s\-]+)\'/', get_option("cufon_replacement_script"), $arr);
		foreach($arr[1] as $f){
			$fontStrArr[] = str_replace(array("'"," "),array("","_"), $f);
		}
		
		// Collapse multiple uses of same font
		$fontStrArr = array_unique($fontStrArr);
		
		// If we have any font names, look for corresponding font files
		if(0 < count($fontStrArr)) {
			// create regex pattern
			$pattern = '/(' . join('|', $fontStrArr) . ')/';

			// Match against font files found
			foreach (glob(WP_PLUGIN_DIR . "/fonts/*.js") as $filename) {    		
				$filename = basename($filename);
				if(preg_match($pattern , $filename, $m)){  		
					?> 
					<!-- WP-Cufon Fonts found  -->
					<script src="<?php echo WP_PLUGIN_URL; ?>/fonts/<?php echo $filename; ?>" type="text/javascript"></script>
					<?php
				} //end if font file matches fonts used
			} //end foreach
		} // end if any font used
		?>                
   
            
<?php } // end function
add_action(get_option("cufon_init_position"), 'wpcufon_init');


function wp_cufon_replacement() {
	?>
	 <!-- WP-Cufon Plugin Replacements --> 
	<script type="text/javascript">
        <?php echo stripslashes(get_option("cufon_replacement_script")); ?> 
    </script>            
	<!-- WP-Cufon END  -->	
	<?php
}
add_action(get_option("cufon_replacement_position"), 'wp_cufon_replacement');


function wp_cufon_delay() {
	?>
	<?php if(get_option("cufon_delay_fix") == "1"){ ?>
    <!-- WP-Cufon Delay Fixes enabled! (rude beta) -->
        <!--[if IE]> 
            <script type="text/javascript" defer="defer">Cufon.now()</script> 
        <![endif]--> 
        <script type="text/javascript">
            Cufon.refresh(); 
            Cufon.now();
        </script>
    <?php }
}
add_action(get_option("cufon_delay_position"), 'wp_cufon_delay');



// add js.tag for better ie6/7 compatibility before </body> ends!
// Note: This step will hopefully become obsolete in the future.
// switches off if delay fix == on.
function wp_cufon_ietag() {
	?>
    <!-- WP-Cufon Plugin (ie6/7 compatibility tag)  -->    
    <?php if(get_option("cufon_delay_fix") == "0"){ ?> <script type="text/javascript"> Cufon.now(); </script><?php } ?>    
<?php }
add_action('wp_footer', 'wp_cufon_ietag');



/*

ADMIN

*/

add_action('admin_menu', 'wpcufon_config_page');

function wpcufon_config_page() {
	if ( function_exists('add_submenu_page') )
		//add_submenu_page('themes.php', __('WP-Cuf&oacute;n'), __('Cuf&oacute;n'), '8', 'wp-cufon.php', 'wpcufon_settings_page');
		add_theme_page( 'WP-Cuf&oacute;n', 'Cuf&oacute;n', 'administrator', 'wp-cufon.php', 'wpcufon_settings_page');
}


 
function admin_register_head() {	    
	?>
    <script language="javascript">	  
		jQuery(document).ready( function($) {		
		   $("#tutorial_button").click(function () {
			  if ($("#tutorial").is(":hidden")) {
				$("#tutorial").slideDown("slow");
				 $("#tutorial_button").text("Hide Tutorial");
			  } else {
				$("#tutorial").hide();
				$("#tutorial_button").text("Show Tutorial");
			  }
			});		
		});	  
	</script>
	<style>
		#tutorial {
		display: none;
		height: auto;
		width: 100%;
		padding:20px;
		border:1px solid #ccc;
    }
    </style>    
    <?php
}
add_action('admin_head', 'admin_register_head');

    



function wpcufon_settings_page() { 
?>	
	<!-- Messages -->
    <?php if ( !empty($_POST ) ) : ?>
	    <div id="message" class="updated fade"><p><strong><?php _e('Options saved.') ?></strong></p></div>
    <?php endif; ?>
    
    <?php
	if(!is_dir(WP_PLUGIN_DIR . "/fonts")) { ?>
		<div id="message" class="updated fade"><p><strong>Please create the /fonts/ Directory in /wp-content/plugins/</strong></p></div>
    <?php } ?>
    
    <div class="wrap">
    <h2>Cuf&oacute;n Administration</h2>
    
    <div class="narrow">
    This Plugin enables <a href="http://wiki.github.com/sorccu/cufon" target="_blank">Cuf&oacute;n</a> in your WordPress Blog.<br />       
    </p>	
   
    
    <h3>--<br />Tutorial</h3>
    <img src="<?php echo WP_PLUGIN_URL; ?>/wp-cufon/help.png" align="bottom" /> <a href="javascript:void(0);" id="tutorial_button">Show Tutorial</a><br /><br />
    
    <div id="tutorial">    
    <h3>How to generate & activate the fonts</h3>
    <ul style="list-style:outside; list-style-type:disc;padding-left:15px;">
      <li><small>First you have to convert your font-file (.ttf, .otf) into a Cuf&oacute;n compatible format.<br />You can do this by using Cuf&oacute;n's own generator at <a href="http://cufon.shoqolate.com/generate/" target="_blank">http://cufon.shoqolate.com/generate/</a></small></li>
      <li><small>In the next step you must create the directory /fonts/ in /wp-content/plugins </small></li>
      <li><small>Then upload your <em>.font.js</em> font-files to <span class="code">/wp-content/plugins/fonts/</span><br />The Plugin will recognize your uploaded fonts and activate them.</small>
      </li>
      <li><small>You can move the &quot;Vegur&quot; Fontfile from /examples/ to your new ../fonts/ directory for testing!</small></li>
      <li><small>If your font doesn't get recognized check the exact font-family FontName in your fontfile and set it right in your replacement code!<br />Check the first line in your font.js file to get the fontName (see <b>bold</b> example below).</small></li>
      <li><small>Example: (in a font.js file) <span class="code">Cufon.registerFont({"w":205,"face":{"font-family":"<b>Vegur Bold</b>", ...</span></small></li>
    </ul>
    <p>&nbsp;</p> 
    
    <h3>Set the font-replacements</h3>
    <small>
        <b>Easy Example:</b><br />
        <span class="code">
            Cufon.set('fontFamily', 'Amaze').replace('h1')('h2');
        </span>
        
        <br /><br />
        
        <b>Replace with different fonts:</b><br />
        <span class="code">
            Cufon.set('fontFamily', 'NiftyFont');<br /><br />
			
            Cufon.replace('#welcome');<br />
            Cufon.replace('#sidebar h2');<br />
            Cufon.replace('#content .title');<br /><br />
            
            Cufon.set('fontFamily', 'AnotherNiceFont');<br />
            
            Cufon.replace('h4');<br />
            Cufon.replace('.quote');<br />
            Cufon.replace('#message');
        </span>
        
        <br /><br />
        
        <b>Access via JavaScript frameworks: (e.g. jQuery needs to be enabled)</b><br />
        <span class="code">
            Cufon.replace('#your_div_id > h1:first-child', { fontFamily: 'FontName With Spaces' });<br />
            Cufon.replace('#your_div_id a', { fontFamily: 'FontName' });<br />
            Cufon.replace('#your_div_id', { fontFamily: 'FontName-Medium' });
        </span>
        
        <br /><br />
        
        <b>Other Examples:</b><br />
        <span class="code">
            Cufon.set('fontFamily', 'Vegur');<br />
            Cufon.replace('#blog-title a', { hover: true });<br />
            Cufon.replace('.menu a', { hover: true, fontWeight: '800' });<br />
            Cufon.replace('.aside h3');<br />
            Cufon.replace('h1.page-title' , { fontWeight: '400' });<br />
            Cufon.replace('h1.entry-title', { fontWeight: '400' });<br />
            Cufon.replace('h2.entry-title', { fontWeight: '400', hover: true });<br />
        </span>
        
        &raquo; <a href="http://wiki.github.com/sorccu/cufon/styling" target="_blank">Get more styling hints</a>        
        <br />  <br />      
        <span style="color:red;">&raquo; If nothing works try to rename your font.js file and your font-family string to the same name and don't use any blanks!</span>
        <br />
        &raquo; For questions about Cuf&oacute;n please visit the <a href="http://groups.google.com/group/cufon" target="_blank">Google Group!</a>
        <br /><br />
        &raquo; Please do not use copyrighted fonts!
        <br />
        &raquo; Cuf&oacute;n is distributed under the <a href="http://en.wikipedia.org/wiki/MIT_License" target="_blank">MIT license</a> by Simo Kinnunen.
    </small>
        
    </div> <!-- /tutorial -->   
       
   <h3>--<br />Your Fonts</h3>
    <?php wpcufon_get_fonts(); ?>
    <p>&nbsp;</p>
   
   
    <?php wpcufon_set_font_replacements(); ?>
    
    </div><!-- narrow -->   
    </div><!-- wrap -->

<?php
}


function wpcufon_get_fonts() {	
	?>
    <p><strong>The plugin automatically found these font-files for Cuf&oacute;n:</strong><br />
    <small>WP-Cuf&oacute;n only activates those fonts you <strong>set</strong> in your replacement-code with a right font-Family name!</small>
    </p>	
    <p>
    <ul>
    <?php
	foreach (glob(WP_PLUGIN_DIR . "/fonts/*.js") as $filename) {
		
		// delete path from filename (looks better)
		$short_filename = basename($filename);		
		?>
		<li>
        <img src="<?php echo WP_PLUGIN_URL; ?>/wp-cufon/icon_ttf.gif" /> <a href="<?php echo WP_PLUGIN_URL .'/fonts/'.$short_filename; ?>" target="_blank"><?php echo $short_filename; ?></a><br />
        <small><strong>Excerpt: </strong><?php $filename_content = file_get_contents ($filename); echo substr($filename_content, 0, 110); ?> ...</small>
		</li>
		<?php
	}
	
	// if empty
	if(!isset($filename)){
		echo"<em>No files found!</em>";
		?><div id="message" class="updated fade"><p><strong>No files found in /wp-content/plugins/fonts/</strong></p></div><?php	
	}
    
	?>
    </ul>
    </p>
    
	<?php
}



/*
	FONT REPLACEMENTS (text src)
*/

$cufon_replacement_script = stripslashes(get_option('cufon_replacement_script'));

if ('saveDB' == $_POST['action']) {
	update_option("cufon_replacement_script",stripslashes($_POST['cufon_replacement_script']));
	update_option("cufon_jquery",$_POST['cufon_jquery']);
	update_option("cufon_delay_fix",$_POST['cufon_delay_fix']);
	update_option("cufon_init_position",$_POST['cufon_init_position']);
	update_option("cufon_replacement_position",$_POST['cufon_replacement_position']);	
	update_option("cufon_delay_position",$_POST['cufon_delay_position']);	
}

$first_script_value = "Cufon.set('fontFamily', 'Vegur').replace('h1')('h2');";
add_option("cufon_replacement_script",stripslashes($first_script_value));
add_option("cufon_jquery","");
add_option("cufon_delay_fix","");
add_option("cufon_init_position","wp_head");
add_option("cufon_replacement_position","wp_head");
add_option("cufon_delay_position","wp_footer");


function wpcufon_set_font_replacements(){
?>
   <h3>--<br />Your Cuf&oacute;n replacement-code</h3>
    
<form name="form1" method="post" action="">
	<textarea class="code" name="cufon_replacement_script" rows="12" cols="80"><?php echo stripslashes(get_option("cufon_replacement_script"));?></textarea>
    <p><em><strong>Hint:</strong> Set all the other font-details in your style.css file -  Cuf&oacute;n will recognize them.</em></p>
    
    <p>&nbsp;</p>
    <h3>--<br />Additional Features</h3>
    	
    <p><label><input name="cufon_jquery" type="checkbox" value="1" <?php if(get_option("cufon_jquery") == "1"){ echo"checked"; }?>  /> Enable jQuery <small>(WP-Cuf&oacute;n will do it only if it's necessary)</small></label></p>
    
    <p><label><input name="cufon_delay_fix" type="checkbox" value="1" <?php if(get_option("cufon_delay_fix") == "1"){ echo"checked"; }?>  /> Delay-Fix - Enables some "Hacks" to get rid of Cuf&oacute;n's delayed appearence on your site <small>(rude and beta)</small></label></p>
    
    <br /><br />
    <h3>--<br />Script Positions</h3>
    <p><small>
    	You can switch the positions from where the scripts should get loaded in your templates sourcecode.<br />
    	This can fix some errors in Firefox (e.g. FireBug Console: Error "9").
    </small>
    </p>
    <table>
    <tr>
    <td style="padding-right:40px;">
    <p>	Cufon Initial-Script:<br />    	
        <label><input name="cufon_init_position" type="radio" value="wp_head" <?php if(get_option("cufon_init_position") == "wp_head"){ echo"checked"; }?>  /><small> Header (Recommended)</small></label><br />
        <label><input name="cufon_init_position" type="radio" value="wp_footer" <?php if(get_option("cufon_init_position") == "wp_footer"){ echo"checked"; }?>  /><small> Footer</small></label>  
    </p>
    </td>
    
    <td style="padding-right:30px;">
    <p>	Cufon Replacement-Script:<br />
    	<label><input name="cufon_replacement_position" type="radio" value="wp_head" <?php if(get_option("cufon_replacement_position") == "wp_head"){ echo"checked"; }?>  /><small> Header (Recommended)</small></label><br />
    	<label><input name="cufon_replacement_position" type="radio" value="wp_footer" <?php if(get_option("cufon_replacement_position") == "wp_footer"){ echo"checked"; }?>  /><small> Footer</small></label>         
    </p>
    </td>
    
    <td>
    <p>	Cufon Delay-Fix:<br />    	
        <label><input name="cufon_delay_position" type="radio" value="wp_head" <?php if(get_option("cufon_delay_position") == "wp_head"){ echo"checked"; }?>  /><small> Header</small></label><br />  
        <label><input name="cufon_delay_position" type="radio" value="wp_footer" <?php if(get_option("cufon_delay_position") == "wp_footer"){ echo"checked"; }?>  /><small> Footer (Recommended)</small></label>
    </p>
    </td>
    </tr>
    </table>
    
    <p>&nbsp;</p>
    
    <div style="float:left; margin-right:30px;"><p><input name="action" value="saveDB" type="hidden" /> <input type="submit" value=" Save Settings " /></p></div>
    <div style="float:left"><a href='http://www.pledgie.com/campaigns/10384' target="_blank"><img alt='Click here to lend your support to: WP-Cufón and make a donation at www.pledgie.com !' src='http://www.pledgie.com/campaigns/10384.png?skin_name=chrome' border='0' /></a></div>
    <div style="clear:both"></div>
    
    
    
   
</form>
    
<?php
}


?>