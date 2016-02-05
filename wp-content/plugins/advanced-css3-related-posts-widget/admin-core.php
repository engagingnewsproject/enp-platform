<?php

//svn version

include 'the_globals.php';

if(isset( $_POST['action'] )) 
	$action = $_POST['action'];
else
	$action = '';

if($action == 'update')

{
	//----------------------------------------------------get the values of array options 

	$rpw_show_thumbs = ''; // Display thumbs or not?
	
		if(isset( $_POST['rpw_show_thumbs'] )) $rpw_show_thumbs = $_POST["rpw_show_thumbs"];

	$rpw_thumbw = ''; // Thumbnail thumb width
	
		if(isset( $_POST['rpw_thumbw'] )) $rpw_thumbw = $_POST["rpw_thumbw"];

	$rpw_thumbh = ''; // Thumbnail thumb height
	
		if(isset( $_POST['rpw_thumbh'] )) $rpw_thumbh = $_POST["rpw_thumbh"];

	$rpw_posts_limit = ''; // How many posts to display?
	
		if(isset( $_POST['rpw_posts_limit'] )) $rpw_posts_limit = $_POST["rpw_posts_limit"];

	$rpw_show_excerpt = '';
	
		if(isset( $_POST['rpw_show_excerpt'] )) $rpw_show_excerpt = $_POST["rpw_show_excerpt"];

	$rpw_excerpt_length = '';
	
		if(isset( $_POST['rpw_excerpt_length'] )) $rpw_excerpt_length = $_POST["rpw_excerpt_length"];

	$rpw_use_css3_effects = '';
	
		if(isset( $_POST['rpw_use_css3_effects'] )) $rpw_use_css3_effects = $_POST["rpw_use_css3_effects"];

	$rpw_css3_shadow = '';
	
		if(isset( $_POST['rpw_css3_shadow'] )) $rpw_css3_shadow = $_POST["rpw_css3_shadow"];

	$rpw_css3_thumb_radius = '';
	
		if(isset( $_POST['rpw_css3_thumb_radius'] )) $rpw_css3_thumb_radius = $_POST["rpw_css3_thumb_radius"];

	$default_thumb = $rpwpluginsurl.'/images/noimage.png'; // Default thumbnail thumb
	
		if(isset( $_POST['default_thumb'] )) $default_thumb = $_POST["default_thumb"];
	
	$rpw_Style = '';
	
		if(isset( $_POST['rpw_Style'] )) $rpw_Style = $_POST["rpw_Style"];
	
	$rpw_text_direction = '';
	
		if(isset( $_POST['rpw_text_direction'] )) $rpw_text_direction = $_POST["rpw_text_direction"];
	
	$rpw_image_direction = '';
	
		if(isset( $_POST['rpw_image_direction'] )) $rpw_image_direction = $_POST["rpw_image_direction"];
	

	//Validation//

	if($rpw_thumbw < 40) $rpw_thumbw = 40;

	if($rpw_thumbh< 40) $rpw_thumbh= 40;

	if($rpw_excerpt_length < 8){$rpw_excerpt_length = '8';}

	if($rpw_excerpt_length > 30){$rpw_excerpt_length = '30';}

	if($rpw_show_thumbs == ''){$rpw_show_thumbs = 'Yes';}

	if($rpw_thumbw == ''){$rpw_thumbw = '40';}

	if($rpw_thumbh == ''){$rpw_thumbh = '40';}

	if($rpw_posts_limit == ''){$rpw_posts_limit = '5';}

	if($rpw_use_css3_effects == ''){$rpw_use_css3_effects = 'None';}

	if($rpw_excerpt_length == ''){$rpw_excerpt_length = '8';}
	
	if($rpw_text_direction == ''){$rpw_text_direction = 'ltr';}
	
	if($rpw_image_direction == ''){$rpw_image_direction = 'left';}

	//-----------------------------------------------------Get general options array values

$rpw_related_posts_settings = 

Array (

		'rpw_show_thumbs' => $rpw_show_thumbs, // Display thumbs or not?

		'rpw_thumbw' => $rpw_thumbw, // Thumbnail thumb width

		'rpw_thumbh' => $rpw_thumbh, // Thumbnail thumb height

		'rpw_posts_limit' => $rpw_posts_limit, // How many posts to display?

		'rpw_show_excerpt' => $rpw_show_excerpt,

		'rpw_excerpt_length' => $rpw_excerpt_length,

		'rpw_use_css3_effects' => $rpw_use_css3_effects,

		'rpw_css3_shadow' => $rpw_css3_shadow,

		'rpw_css3_thumb_radius' => $rpw_css3_thumb_radius,

		'default_thumb' => $default_thumb, // Default thumbnail thumb
		
		'rpw_Style' => $rpw_Style,
		
		'rpw_image_direction' => $rpw_image_direction,
		
		'rpw_text_direction' => $rpw_text_direction
	);

	if ($rpw_related_posts_settings != '' ) {

	    update_option( 'rpw_settings' , $rpw_related_posts_settings );

	} else {

	    $deprecated = ' ';

	    $autoload = 'no';

	    add_option( 'rpw_settings', $rpw_related_posts_settings, $deprecated, $autoload );

	}

}else //no update action

{

	$rpw_related_posts_settings = rpw_read_options();

}
?>

<style>

#rpw_admin_main {

text-align:left;

direction:ltr;

padding:10px;

margin: 10px;

background-color: #ffffff;

border:1px solid #EBDDE2;

display: relative;

overflow: auto;

}

.inner_block{

height: 370px;

display: inline;

min-width:770px;

}

#donate{

    background-color: #EEFFEE;

    border: 1px solid #66DD66;

    border-radius: 10px 10px 10px 10px;

    height: 58px;

    padding: 10px;

    margin: 15px;

    }
#rpwbox1{
    position:relative;
}
#rpwbox1:after{
       content:url(<?php echo $rpwpluginsurl; ?>/images/rpw-promo.png);
       display:block;
       position:absolute;
       top: 10px;
       right: 10px;
}
#rpwbox2{
    position:relative;
}
#rpwbox2:after{
       content:url(<?php echo $rpwpluginsurl; ?>/images/rpw-css3-promo.png);
       display:block;
       position:absolute;
       top: 10px;
       right: 10px;
}
</style>
<div id="rpw_admin_main">
<form name="rpwform" method="POST">
<script type="text/javascript">
function change_style_options(rpw_Style)
{
	var rpw_show_thumbs,rpw_thumbw,rpw_thumbh,rpw_posts_limit,rpw_show_excerpt,rpw_excerpt_length,rpw_use_css3_effects,rpw_css3_shadow,rpw_css3_thumb_radius,rpw_image_direction,rpw_text_direction;
	if(rpw_Style == 'Thumbs_Left'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'Yes';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'No';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = 'None';
		rpw_image_direction = 'left';
		rpw_text_direction = 'ltr';
	}
	if(rpw_Style == 'Thumbs_Right'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'Yes';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'No';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = 'None';
		rpw_image_direction = 'right';
		rpw_text_direction = 'rtl';
	}
	if(rpw_Style == 'Big_Thumbs'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '170';
		rpw_thumbh = '110';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'No';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'No';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = 'None';
		rpw_image_direction = 'center';
		rpw_text_direction = 'center';
	}
	if(rpw_Style == 'Wide_Thumbs'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '230';
		rpw_thumbh = '70';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'No';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'No';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = 'None';
		rpw_image_direction = 'center';
		rpw_text_direction = 'center';
	}
	if(rpw_Style == 'No_Thumbs'){
		rpw_show_thumbs = 'No';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'Yes';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'No';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = 'None';
		rpw_image_direction = 'left';
		rpw_text_direction = 'ltr';
	}
	if(rpw_Style == 'Just_Thumbs'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '9';
		rpw_show_excerpt = 'No';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'No';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = 'None';
		rpw_image_direction = 'left';
		rpw_text_direction = 'center';
	}
		if(rpw_Style == 'CSS-Thumbs_Left'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'Yes';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'Yes';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = '45';
		rpw_image_direction = 'left';
		rpw_text_direction = 'ltr';
	}
	if(rpw_Style == 'CSS-Thumbs_Right'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'Yes';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'Yes';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = '45';
		rpw_image_direction = 'right';
		rpw_text_direction = 'rtl';
	}
	if(rpw_Style == 'CSS-Big_Thumbs'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '170';
		rpw_thumbh = '110';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'No';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'Yes';
		rpw_css3_shadow = '10';
		rpw_css3_thumb_radius = '10';
		rpw_image_direction = 'center';
		rpw_text_direction = 'center';
	}
	if(rpw_Style == 'CSS-Wide_Thumbs'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '230';
		rpw_thumbh = '70';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'No';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'Yes';
		rpw_css3_shadow = '10';
		rpw_css3_thumb_radius = '10';
		rpw_image_direction = 'center';
		rpw_text_direction = 'center';
	}
	if(rpw_Style == 'CSS-No_Thumbs'){
		rpw_show_thumbs = 'No';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '7';
		rpw_show_excerpt = 'Yes';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'No';
		rpw_css3_shadow = 'None';
		rpw_css3_thumb_radius = 'None';
		rpw_image_direction = 'left';
		rpw_text_direction = 'ltr';
	}
	if(rpw_Style == 'CSS-Just_Thumbs'){
		rpw_show_thumbs = 'Yes';
		rpw_thumbw = '40';
		rpw_thumbh = '40';
		rpw_posts_limit = '9';
		rpw_show_excerpt = 'No';
		rpw_excerpt_length = '14';
		rpw_use_css3_effects = 'Yes';
		rpw_css3_shadow = '10';
		rpw_css3_thumb_radius = '45';
		rpw_image_direction = 'left';
		rpw_text_direction = 'center';
	}
	document.rpwform.rpw_show_thumbs.value = rpw_show_thumbs;
	document.rpwform.rpw_thumbw.value = rpw_thumbw;
	document.rpwform.rpw_thumbh.value = rpw_thumbh;
	document.rpwform.rpw_posts_limit.value = rpw_posts_limit;
	document.rpwform.rpw_show_excerpt.value = rpw_show_excerpt;
	document.rpwform.rpw_excerpt_length.value = rpw_excerpt_length;
	document.rpwform.rpw_use_css3_effects.value = rpw_use_css3_effects;
	document.rpwform.rpw_css3_shadow.value = rpw_css3_shadow;
	document.rpwform.rpw_css3_thumb_radius.value = rpw_css3_thumb_radius;
	document.rpwform.rpw_image_direction.value = rpw_image_direction;
	document.rpwform.rpw_text_direction .value = rpw_text_direction;
}
</script>

<input type="hidden" value="update" name="action">
<div class="">
	<h2>Related Posts Widget Options:</h2>
</div>
<div class="simpleTabs">
<ul class="simpleTabsNavigation">
    <li><a href="#">Classic layouts</a></li>
    <li><a href="#">Advanced Options</a></li>
    <li><a href="#">About</a></li>
</ul>
<div class="simpleTabsContent" style="height: 401px; border: 1px solid #E9E9E9; padding: 4px">
<div id="rpwbox1">
	&nbsp;<table border="0" width="40%">
		<tr>
			<td align="center"><?php $rpw_Style = $rpw_related_posts_settings['rpw_Style'];?>
			<?php $checkvalue = ''; if($rpw_Style == 'Thumbs_Left'){ $checkvalue = 'checked';}?>
			<input onclick="change_style_options('Thumbs_Left');" type="radio" name="rpw_Style" value="Thumbs_Left" <?php echo $checkvalue ?>>Thumbs 
			Left</td>
			<td align="center">
			<?php $checkvalue = ''; if($rpw_Style == 'Thumbs_Right'){ $checkvalue = 'checked';}?>
			<input onclick="change_style_options('Thumbs_Right');" type="radio" name="rpw_Style" value="Thumbs_Right" <?php echo $checkvalue ?>>Thumbs 
			Right</td>
			<td align="center">
			&nbsp;</td>
		</tr>
		<tr>
			<td align="center">
			<img border="0" src="<?php echo $rpwpluginsurl; ?>/images/thumbsleft.png" width="50" height="86"></td>
			<td align="center">
			<img border="0" src="<?php echo $rpwpluginsurl; ?>/images/thmbsright.png" width="50" height="86"></td>
			<td align="center">
			&nbsp;</td>
		</tr>
		<tr>
			<td align="center">
			&nbsp;</td>
			<td align="center">
			&nbsp;</td>
			<td align="center">
			&nbsp;</td>
		</tr>
		<tr>
			<td align="center">
			&nbsp;</td>
			<td align="center">
			&nbsp;</td>
			<td align="center">
			&nbsp;</td>
		</tr>
		<tr>
			<td align="center" colspan="2">
			Need more modern and new layouts (premium version)?</td>
			<td align="center">
			&nbsp;</td>
		</tr>
		<tr>
			<td align="center" colspan="2">
			<b><font size="5">
			<a href="http://www.wp-buy.com/product/css3-related-posts-widget-premium-version/">Live Preview</a></font></b></td>
			<td align="center">
			&nbsp;</td>
		</tr>
	</table>
	</div>
</div>
<div class="simpleTabsContent" style="height: 597px; border: 1px solid #E9E9E9; padding: 4px" id="layer1">

	<table border="0" width="100%" height="100%" cellspacing="0" cellpadding="0">

			<tr>

				<td width="480" colspan="4" height="45">
	<h2>CSS3 Related Posts Widget Options:</h2></td>

			</tr>

			<tr>

				<td width="22%" colspan="2" style="border-bottom: 1px solid #F7F7F7">Show thumbnails</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7"><select size="1" name="rpw_show_thumbs">

				<?php 

				if ($rpw_related_posts_settings['rpw_show_thumbs'] == 'Yes')

					{

						echo '<option selected>Yes</option>';

						echo '<option>No</option>';

					}

					else

					{

						echo '<option>Yes</option>';

						echo '<option selected>No</option>';

					}

				?>

				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">&nbsp;</td>

			</tr>

			<tr>

				<td width="172" style="border-bottom: 1px solid #F7F7F7">Thumbnail width</td>

				<td title="Best Value: 85px" width="143" style="border-bottom: 1px solid #F7F7F7"><?php echo "<img title='Best Value: 85px' src='$rpwpluginsurl/images/what.gif' align='center' />";?></td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7">

				<input type="text" name="rpw_thumbw" size="12" value="<?php echo $rpw_related_posts_settings['rpw_thumbw']; ?>"> 
				px</td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				<font color="#008000">Best value: 40</font></td>

			</tr>

			<tr>

				<td width="172" style="border-bottom: 1px solid #F7F7F7">Thumbnail height</td>

				<td title="Best Value: 85px" width="143" style="border-bottom: 1px solid #F7F7F7"><?php echo "<img title='Best Value: 85px' src='$rpwpluginsurl/images/what.gif' align='center' />";?></td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7">

				<input type="text" name="rpw_thumbh" size="12" value="<?php echo $rpw_related_posts_settings['rpw_thumbh']; ?>"> 
				px</td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				<font color="#008000">Best value: 40</font></td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">Posts limit</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7"><select size="1" name="rpw_posts_limit">

				<?php for($i=1;$i<=9;$i++)

				{

					if ($i == $rpw_related_posts_settings['rpw_posts_limit'])

						echo '<option selected>'. $i .'</option>';

					else

						echo '<option>'. $i .'</option>';

				}

				?>

				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				<font color="#008000">Default: 7</font></td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">Show Excerpt</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7"><select size="1" name="rpw_show_excerpt">
				<?php

				$choice = '';

				$rpw_show_excerpt_temp = $rpw_related_posts_settings['rpw_show_excerpt']; ?>

				<?php if ($rpw_show_excerpt_temp == 'Yes'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="Yes">Yes</option>

				<?php if ($rpw_show_excerpt_temp == 'No'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="No">No</option>

				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				&nbsp;</td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">Excerpt length in words</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7">

				<input type="text" name="rpw_excerpt_length" size="12" value="<?php echo $rpw_related_posts_settings['rpw_excerpt_length']; ?>">words</td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				<font color="#008000">Best value: 14</font></td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">Use CSS3 Effects</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7">

				<select size="1" name="rpw_use_css3_effects">
				<?php

				$choice = '';

				$rpw_use_css3_effects_temp = $rpw_related_posts_settings['rpw_use_css3_effects']; ?>

				<?php if ($rpw_use_css3_effects_temp == 'Yes'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="Yes">Yes</option>

				<?php if ($rpw_use_css3_effects_temp == 'No'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="No">No</option>
				
				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				&nbsp;</td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">CSS3 (shadow) effect</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7"><select size="1" name="rpw_css3_shadow">

				<?php 

				$choice = '';

				$css3_temp = $rpw_related_posts_settings['rpw_css3_shadow']; ?>

				<?php if ($css3_temp == 'None'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="None">None</option>

				<?php if ($css3_temp == '5'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="5">shadow 5px small</option>

				<?php if ($css3_temp == '10'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="10">shadow 10px medium</option>

				<?php if ($css3_temp == '15'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="15">shadow 15px big</option>

				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				&nbsp;</td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">CSS3 (radius) effect</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7"><select size="1" name="rpw_css3_thumb_radius">

				<?php

				$choice = '';

				$css3_temp = $rpw_related_posts_settings['rpw_css3_thumb_radius']; ?>

				<?php if ($css3_temp == 'None'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="None">None</option>

				<?php if ($css3_temp == '10'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="10">small radius 10px</option>

				<?php if ($css3_temp == '20'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="20">medium radius 20px</option>

				<?php if ($css3_temp == '45'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="45">rounded radius</option>

				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				&nbsp;</td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">Image direction</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7"><select size="1" name="rpw_image_direction">

				<?php

				$choice = '';

				$rpw_image_dir_temp = $rpw_related_posts_settings['rpw_image_direction']; ?>

				<?php if ($rpw_image_dir_temp == 'left'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="left">Left</option>
				
				<?php if ($rpw_image_dir_temp == 'center'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="center">Center</option>

				<?php if ($rpw_image_dir_temp == 'right'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="right">Right</option>

				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				&nbsp;</td>

			</tr>

			<tr>

				<td width="177" colspan="2" style="border-bottom: 1px solid #F7F7F7">Text direction</td>

				<td width="178" style="border-bottom: 1px solid #F7F7F7"><select size="1" name="rpw_text_direction">

				<?php

				$choice = '';

				$rpw_text_direction_temp = $rpw_related_posts_settings['rpw_text_direction']; ?>

				<?php if ($rpw_text_direction_temp == 'ltr'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="ltr">Left To Right</option>
				
				<?php if ($rpw_text_direction_temp == 'center'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="center">Center</option>

				<?php if ($rpw_text_direction_temp == 'rtl'){$choice = 'selected';}else{$choice = '';} ?>

				<option <?php echo $choice ?> value="rtl">Right To Left</option>

				</select></td>

				<td width="125" style="border-bottom: 1px solid #F7F7F7">

				&nbsp;</td>

			</tr>

			<tr>

				<td width="480" colspan="4">&nbsp;</td>

			</tr>
	</table>
	
	
	
		</div>
<!-- new tab -->
<div class="simpleTabsContent" style="border: 1px solid #E9E9E9; padding: 4px">
	<h3>Description:</h3>
	<p>Here is wonderful widget for displaying links to related posts beneath 
	each of your wordpress blog posts. The related articles are chosen from 
	other posts in that same tag. With this plugin many of your readers will 
	remain on your site for longer periods of time when they see related posts 
	of interest.<img width="450" border="0" src="<?php echo $rpwpluginsurl; ?>/images/widget-recent-portfolios.png" align="right"></p>
	<p>Our plugin displaying related posts in a very great way as a sidebar 
	widget to help visitors staying longer on your site. You can use this plugin 
	to increasing the page rank of your internal posts to improve your SEO score 
	and increase the internal links priority in google webmaster tools</p>
	<h3>More WordPress Plugins:</h3>
	<table border="0" width="50%" cellspacing="0" cellpadding="4">
		<tr>
			<td align="center"><font color="#1F81D1" style="font-size: 13pt">
			<img class="aligncenter wp-image-557 size-thumbnail" src="http://www.wp-buy.com/wp-content/uploads/2014/01/box-wpcp-150x150.png" alt="" height="150" width="150"></font><a href="http://www.wp-buy.com/product/wp-content-copy-protection-pro/" style="text-decoration: none"><h3>
			<font color="#1F81D1" style="font-size: 13pt">WP Content Copy 
			Protection (pro)</font></h3>
			</a></td>
			<td align="center"><font color="#1F81D1" style="font-size: 13pt">
			<img class="aligncenter size-thumbnail wp-image-1030" src="http://www.wp-buy.com/wp-content/uploads/2015/01/png-150x150.png" alt="" height="150" width="150"></font><a href="http://www.wp-buy.com/product/visitors-traffic-real-time-statistics-pro/" style="text-decoration: none"><h3>
			<font color="#1F81D1" style="font-size: 13pt">Visitors Traffic Real 
			Time Statistics pro</font></h3>
			</a></td>
		</tr>
		<tr>
			<td align="center"><font color="#1F81D1" style="font-size: 13pt">
			<img class="aligncenter wp-image-1437 size-thumbnail" src="http://www.wp-buy.com/wp-content/uploads/2015/05/admin-ajax-copy-150x150.jpg" alt="admin-ajax copy" height="150" width="150"></font><a href="http://www.wp-buy.com/product/giga-slider-pro/" style="text-decoration: none"><h3>
			<font color="#1F81D1" style="font-size: 13pt">Responsive Media 
			Slideshow – GIGA Slider Pro</font></h3>
			</a></td>
			<td align="center"><font color="#1F81D1" style="font-size: 13pt">
			<img src="http://www.wp-buy.com/wp-content/uploads/2015/08/wp-tree-128x162.png" class="attachment-shop_thumbnail wp-post-image" alt="wp tree" height="162" width="128"></font><a href="http://www.wp-buy.com/product/wp-tree-pro/" style="text-decoration: none"><h3>
			<font color="#1F81D1" style="font-size: 13pt">Vertical and 
			horizontal Tree WP-Tree pro</font></h3>
			</a></td>
		</tr>
	</table>
	</div>
<!-- /new tab -->
</div><!-- simple tabs div end -->		
		



<div>
	<p align="right">
	&nbsp;&nbsp; <input type="submit" value="     Save Settings     " name="B4">&nbsp;&nbsp;</p>
</div>

	</li>

</form></div>

<p>&nbsp;</p>