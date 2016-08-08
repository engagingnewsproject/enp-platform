=== Custom Menu Class ===
Contributors: Theodoros Fabisch
Tags: menu, classes, menu class, css class, css classes, predefined css class
Requires at least: 3.7
Tested up to: 4.5
Stable tag: trunk
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Set predefined CSS classes to menu items

== Description ==

Simple plugin that adds extra functionality to menu items. The plugin will allow to set predefined CSS classes (Select field) to menu items.
Support for the plugin "If Menu": http://wordpress.org/plugins/if-menu/ - does not break the "If Menu" plugin.

Example of defining CSS classes for menu items is in the "FAQ" tab here.

Custom Menu Class is 100% free. if you have questions or need additional information u can comment on my website ( http://deving.de - http://deving.de/blog/wordpress/2292-wordpress-plugin-fuer-voreingestellte-css-klassen-fuer-menue-links/ ) or in the "Support" tab here.

Check out my [Themeforest Account](http://themeforest.net/user/Aiken1/portfolio?ref=Aiken1 "Themeforest")

== Installation ==

To install the plugin, follow the steps below

1. Upload `custom-menu-class` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Define CSS classes for menu items in the 'Options -> Menu CSS Classes' settings page
4. Set predefined CSS classes for your menu items in 'Appearance -> Menus' page - Choose CSS classes from the select field (multiple selection is possible)

== Frequently Asked Questions ==

= How can I set the CSS classes? =

Set the CSS classes in the Custom Menu Class settings page under 'Options -> Menu CSS Classes'

== Screenshots ==

1. Here's a screenshot of it in action

== Changelog ==

= 0.1 =
* Plugin release. Basis for this plugin is "If Menu": http://wordpress.org/plugins/if-menu/

= 0.1.2 =
* Added screenshot
* Bugfix: Filter function name

= 0.2.0 =
* Added plugin settings page for CSS classes (Options -> Menu CSS Classes)

= 0.2.1 =
* Fullwidth select field for menu CSS classes

= 0.2.2 =
* Bugfix: Added wp_reset_query
* Bugfix: Changed _e() to __()

= 0.2.3 =
* Notice: removed deprecated argument caller_get_posts and added ignore_sticky_posts

= 0.2.4 =
* changed wp_reset_query to wp_reset_postdata

= 0.2.5 =
* changed WP_Query to get_posts

= 0.2.6 =
* bugfix get_post_meta in wp 4.5

= 0.2.6.1 =
* bugfix get_post_meta in wp < 4.5