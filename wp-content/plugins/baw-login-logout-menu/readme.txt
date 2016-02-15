=== Login Logout Menu ===
Contributors: juliobox, GregLone
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=KJGT942XKWJ6W
Tags: login, log in, logout, menu, nonce
Requires at least: 3.0
Tested up to: 4.3
Stable tag: trunk

Add real ‘Log in’ and ‘Logout’ links into your WordPress menus!

== Description ==

With this plugin you can now add a real log in/logout item menu with autoswitch when user is logged in or not.
Nonce token is present on logout item.
2 titles, one for 'log in' and one for 'logout' can be set up.
Also, you can set the redirecion page you want, just awesome.

== Installation ==

1. Upload the *"baw-login-logout-menu"* folder into the *"/wp-content/plugins/"* directory
1. Activate the plugin through the *"Plugins"* menu in WordPress
1. You can now add real log in and logout links in your Navigation Menus
1. See FAQ for usage

== Frequently Asked Questions ==

= How does this works? =

Visit your navigation admin menu page, you got a new box including 3 links, 'log in', 'logout', 'log in/logout'.
Add the link you want, for example "Log in|Logout"
1. You can change the 2 titles links, just separate them with a | (pipe)
1. You can add a page for redirection, example #bawloginout#index.php This will redirect users on site index.
1. You can add 2 pages for redirection, example #bawloginout#login.php|logout.php This will redirect users too.
1. For this redirection you can use the special value %actualpage%, this will redirect the user on the actual page.

You can also add 3 shortcodes inyour theme template or in your pages/posts. just do this :
For theme : `<?php echo do_shortcode( '[loginout]' ); ?>`
In you posts/pages : `[loginout]`

The 3 shortcodes are "[login]", "[logout]" and "[loginout]".
You can set 2 parameters, named "redirect" and "edit_tag".
Redirect: used to redirect the user after the action (log in or out) ; example : "/welcome/" or "index.php"
Edit_tag: used to modify the <a> tag, ; example " class='myclass'" or " id='myid' class='myclass' rel='friend'" etc

You can also modify the title link with [login]Clic here to connect[/login] for example

There is a new hook (1.3.3) named 'bawregister_item", this is a menu item, when a user is not logged in, it contains the register button/link, and when the user is logged in, since the link will diseppear, you can hook it and change the ttle and URL, so it won't go away.

== Screenshots ==

1. The meta box in nav menu admin page

== Changelog ==

= 1.3.3 =
* 31 july 2015
* New hook, see FAQ
* Code compliant

= 1.3 =
* 09 nov 2012
* Fix the famous NULL ITEM bug! Thanks for your patience :)
* File splitting
* Fix a graphic bug on the menu spinner

= 1.2 =
* 29 jun 2012
* You can now add 2 pages for the #bawloginout# choice, check the FAQ

= 1.1 =
* 13 mar 2012
* 3 shortcodes added, see FAQ

= 1.0 =
* 08 mar 2012
* First release


== Upgrade Notice ==

None