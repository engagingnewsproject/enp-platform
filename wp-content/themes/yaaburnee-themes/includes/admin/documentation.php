<?php
global $different_themes_managment;
$differentThemes_documentation_options= array(
 array(
	"type" => "navigation",
	"name" => "Contact Info",
	"slug" => "contact"
),

array(
	"type" => "tab",
	"slug"=>'documentation'
),

array(
	"type" => "sub_navigation",
	"subname"=>array(
		array("slug"=>"documentation", "name"=>"Contact Info")
		)
),

/* ------------------------------------------------------------------------*
 * DOCUMENTATION SLIDER SETTINGS
 * ------------------------------------------------------------------------*/

array(
	"type" => "sub_tab",
	"slug"=>'documentation'
),

array(
	"type" => "row"
),

 array(
	"type" => "text",
	"text"=>'<h2>'.THEME_FULL_NAME.' Support </h2>
			<p>Please note that there is a documentation included where all the theme customization settings are explained. The documentation is located within the documentation folder, or You can view an example of documentation here: <a href="http://'.THEME_NAME.'.different-themes.com/documentation/"><strong>http://'.THEME_NAME.'.different-themes.com/documentation/</strong></a></p>
			<h2 style="padding-top:30px;">If you have any questions </h2>
			<p>It is faster for us to communicate with clients using e-mail, so please consider looking for support via e-mail <a href="mailto:support@different-themes.com"><strong>support@different-themes.com</strong></a></p>

			<h2 style="padding-top:30px;">Theme support policy</h2>
			<em>Different Themes reserve the right to reject support to a theme if any of the following conditions are violated or not obeyed.</em>
			<ul>
				<li>Reread the documentation prior to contacting us. Ensure that your problem is not described there. The documentation is added to every theme, as well as description of every theme contains a link to the documentation.</li>
				<li>
			<p lang="en-US">We support only themes developed by Different Themes.</p>
			</li>
				<li>
			<p lang="en-US">Themes are not for commercial use, excluding if an Extended License is purchased.</p>
			</li>
				<li>
			<p lang="en-US">Ensure that your server supports the latest PHP version; ensure as well that the particular Wordpress version is supported by the theme. We suggest updates to be performed always, except cases indicated in the theme description.</p>
			</li>
				<li>
			<p lang="en-US">Check if the problem remains after you turn off all Wordpress plug-ins.</p>
			</li>
				<li>Use Support Centre on the right side of ThemeForest to contact us <span style="color: #000080;"><span style="text-decoration: underline;"><a href="http://themeforest.net/user/different-themes/">http://themeforest.net/user/different-themes/</a></span></span>. Or send an email to <span style="color: #000080;"><span style="text-decoration: underline;"><a href="mailto:support@different-themes.com"><span style="font-family: Arial, sans-serif;">support@different-themes.com</span></a></span></span> (indicate the purchase number you received when ordering the theme).</li>
				<li>
			<p lang="en-US">When contacting the support indicate the title of the theme in a theme field of the email.</p>
			</li>
				<li>In the body of the email indicate a precise version of your theme. You can see it at ThemeForest.net by every particular theme.</li>
				<li>
			<p lang="en-US">It would foster the process if you indicate also wp-admin access data.</p>
			</li>
				<li>Indicate also a type of browser and its version where the error appeared.</li>
				<li>If there are any visual problems it is advisable to add a screenshot of the page where the error appears.</li>
				<li>
			<p lang="en-US">Try to describe the problem precisely and in details. In case of necessity, add a link to the image.</p>
			</li>
				<li>
			<p lang="en-US">We do not guarantee that the theme is compatible with all available plug-ins. There may be cases when plug-in is incompatible with the theme and has to be turned off.</p>
			</li>
				<li>
			<p lang="en-US">We do not provide support if the theme has been modified, i.e., if any files have been modified and afterwards it does not work, then we do not provide support. Excluding cases when a separate order has been made and paid for.</p>
			</li>
				<li>
			<p lang="en-US">Any supplements or modifications of the theme shall be formalized as a separate purchase.</p>
			</li>
				<li>
			<p lang="en-US">The reply shall be given within one working day.</p>
			</li>
				<li>Different Themes reserves the right to amend the terms of usage without a prior notice!</li>
			</ul>
'
),

array(
	"type" => "close"
),

array(
	"type" => "save",
	"title" => "Save Changes"
),

array(
	"type" => "closesubtab"
),

array(
	"type" => "closetab"
)
 
 
);

$different_themes_managment->add_options($differentThemes_documentation_options);
 
?>