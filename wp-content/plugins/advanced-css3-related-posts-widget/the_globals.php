<?php
$option_name = 'RPW_Related_Posts_Type';
$ver_type = 'free';
//Get The Plugins URL as http://www.yrsite.com/dir/subdir
	$rpwpluginsurl = plugins_url( '', __FILE__ );
//get the tag id's as al list
$reg_exp = '|<img.*?src=[\'"](.*?)[\'"].*?>|';
$new_reg_exp = '@<img.+src="(.*)".*>@Uims';
//echo $myoperator;
?>