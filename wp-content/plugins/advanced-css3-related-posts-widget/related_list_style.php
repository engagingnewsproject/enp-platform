<?php ob_start(); 

//define all variables the needed alot

include 'the_globals.php';

$rpw_related_posts_settings = rpw_read_options();

?>

<style type="text/css">

.related_posts_rpw_main_image {

	<?php $rpw_image_direction = $rpw_related_posts_settings['rpw_image_direction']; ?>

	float:<?php echo $rpw_image_direction;?>;
	
	<?php
	if($rpw_image_direction == 'left')
		echo "margin: 0px 10px 0px 0px;";
	else
		echo "margin: 0px 0px 0px 10px;";
	?>
    padding: 0px;
}

.related_posts_rpw_main_image img{

    background: none repeat scroll 0 0 #FFFFFF;

    margin: 0px 0px 0px 0px;

    padding: 0px;

}

.related_posts_rpw_main_image a img{

//border: 3px solid #f5f5f5;

}

<?php

$rpw_use_css3_effects = $rpw_related_posts_settings['rpw_use_css3_effects'];

$rpw_css3_shadow = $rpw_related_posts_settings['rpw_css3_shadow'];

if($rpw_use_css3_effects == 'Yes'){ ?>

.related_posts_rpw_main_image img {

position: relative;

-webkit-box-shadow: 0 0 <?php echo $rpw_css3_shadow; ?>px #666666;// Safari and chrome

-khtml-box-shadow: 0 0 <?php echo $rpw_css3_shadow; ?>px #666666;// Linux browsers

behavior: url(ie-css3.htc);

-moz-box-shadow: 0px 0px <?php echo $rpw_css3_shadow; ?>px #666666;

box-shadow: 0px 0px <?php echo $rpw_css3_shadow; ?>px #666666;

z-index: 2;

behavior: url(<?php echo $rpwpluginsurl; ?>/ie-css3.htc);

}

<?php } ?>


<?php 

$rpw_css3_thumb_radius = $rpw_related_posts_settings['rpw_css3_thumb_radius'];

if($rpw_css3_thumb_radius != 'None'){ ?>

	.related_posts_rpw_main_image img {

		-webkit-border-radius: <?php echo $rpw_css3_thumb_radius; ?>px;// Safari and chrome
		
		-khtml-border-radius: <?php echo $rpw_css3_thumb_radius; ?>px;// Linux browsers
		
		behavior: url(ie-css3.htc);
		
		-moz-border-radius: <?php echo $rpw_css3_thumb_radius; ?>px;
		
		border-radius: <?php echo $rpw_css3_thumb_radius; ?>px;
		
		behavior: url(<?php echo $rpwpluginsurl; ?>/ie-css3.htc);}

<?php } ?>

.imgshadow_light {

    background: none repeat scroll 0 0 #FFFFFF;

    border: 1px solid #777777;

    box-shadow: 0 0 5px #666666;

}

.related_posts_rpw_time {

	position: absolute;display: block;

	color: #CCCCCC;direction: ltr;font: 13px/28px sans-serif;z-index: 1;right: 15px;top: 0px;

	text-align: right;

	width: 120px;

}

#related_posts_rpw h3{

	text-align:left;

	direction:ltr;

	font-size: 14px;

	border: medium none;

	padding: 0px;

	margin: 0px;

}

#related_posts_rpw ul h3{

	width:88%;

}
#related_posts_rpw
{
	text-align: center;
	display: table;
}
.related_posts_rpw_main_content {

	<?php $rpw_text_direction =  $rpw_related_posts_settings['rpw_text_direction']; ?>
	direction:<?php echo $rpw_text_direction;?>;
	text-align: <?php echo $rpw_text_direction;?>;

}

.related_posts_rpw_main_content p{

	$rpw_image_direction = $rpw_related_posts_settings['rpw_image_direction'];

	text-align:<?php echo $rpw_image_direction;?>;

	color:#4c4c4c;
	
	$rpw_text_direction = $rpw_related_posts_settings['rpw_text_direction'];

	direction:<?php echo $rpw_text_direction;?>;
	
	margin: 0px 0px 2px 0px;

}

#related_posts_rpw ul{

	margin:0px;

	padding: 0 8px;
	
	justify-content: flex-start  flex-end  center   space-between  space-around;
}

#related_posts_rpw li{

	display: block;
<?php
$rpw_Style = $rpw_related_posts_settings['rpw_Style'];
if($rpw_Style != "Just_Thumbs" && $rpw_Style != "Big_Thumbs" && $rpw_Style != "Wide_Thumbs" && $rpw_Style != "CSS-Just_Thumbs" && $rpw_Style != "CSS-Big_Thumbs" && $rpw_Style != "CSS-Wide_Thumbs"){ ?>
	border-bottom: 1px dotted #CCCCCC;
	margin: 0 0 6px;
<?php } ?>

	padding: 0 0 6px;

<?php if($rpw_Style == "Just_Thumbs" || $rpw_Style == "CSS-Just_Thumbs" ){ ?>
	float: left;
<?php } ?>

}

#related_posts_rpw li:after{

	clear: both;

	content: ".";

	display: block;

	height: 0;

	line-height: 0;

	visibility: hidden;

}
<?php
$rpw_thumbw = $rpw_related_posts_settings['rpw_thumbw'];

$rpw_thumbh = $rpw_related_posts_settings['rpw_thumbh'];
?>
#related_posts_rpw li img{

	border: 1px solid #EEEEEE;

    //float: left;

    margin-right: 2px;

    padding: 3px;
    
    width: <?php echo $rpw_thumbw;?>px;
    
    height: <?php echo $rpw_thumbh;?>px;
}

#entry-meta-span {

    background: none repeat scroll 0 0 #000000;

    color: #CCCCCC;

    display: block;

    font-size: 11px;

    height: 20px;

    margin: -95px 470px 0;

    opacity: 0.55;

    position: relative;

    text-align: center;

    width: 100%;

    z-index: 99;

    border-radius: 15px 15px 15px 15px;

    -moz-border-radius: 15px 15px 15px 15px;

    -webkit-border-radius: 15px 15px 15px 15px;

    -khtmlborder-radius: 15px 15px 15px 15px;

}

.credit-span,.credit-span a{

color:#8B8B8B;

font-size: 9pt;

text-align:right;

text-decoration:none;

}

//#related_posts_rpw li:hover{background:#F9F9F9;-moz-transition: all 0.3s ease-out 0s;}

</style>

<?php

$out = ob_get_clean();

return $out;

?>