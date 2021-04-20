=== Engaging Buttons ===
Contributors: jeryj, luke-carl
Donate link: https://utdirect.utexas.edu/apps/utgiving/online/nlogon/?menu=COEN
Tags: buttons, like, engagement, research-based, shortcode, widget, posts, pages, custom post types, comments, custom, vote, voting, favorite, most liked, promoted, featured, extendable, filters, hooks, respect, important, recommend, useful, thoughtful
Requires at least: 4.0
Tested up to: 5.7
Stable tag: 1.0.6
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Easily add research-based, engaging buttons (such as "Respect" or "Important") to your site.


== Description ==

It’s easy to click “Like” on a heartwarming story about a local hero. But what about a fair, but counter-attitudinal, post in a comment section? That may make it a little more challenging to “Like.”

Through [our research on social media buttons](http://mediaengagement.org/research/engagement-buttons/), we found that **people were more likely to click “Respect” over “Like”** in comment sections, and significantly more likely to click “Respect” when the view expressed differed from their own.

**The Engaging Buttons WordPress plugin allows you to easily set-up and configure alternative buttons to “Like” (and lots more!) on your website.**

The Engaging Buttons plugin is made by the [Center for Media Engagement](http://mediaengagement.org). The Center for Media Engagement (CME) provides research-based techniques for engaging online audiences in commercially viable and democratically beneficial ways.

**Features**

- **Create buttons** for your website: Respect, Recommend, Important, Useful, and/or Thoughtful
- **Customize your button** by selecting the design and colors that best fits your website.
- **Easily display your Top Posts** with the Engaging Button widget, shortcode, or with one click from the settings page.
- (Optional) Send click data back to the Center for Media Engagement so we can continue to provide free, research-based, high-quality plugins and update the plugin with high-engagement words.
- **Customizable by developers** via CSS and PHP (WordPress filters and hooks).
- **Lightweight & Performance-focused**: Only adds 2 files and ~2kb to your site (with GZIP compression).


== Installation ==

1. Download the plugin .zip file
2. Go to your WordPress admin panel, and go to Plugins > Add New
3. Click the "Upload Plugin" button
4. Choose the plugin .zip file from your computer and click the "Install Now" button
5. Activate the plugin after it's done installing.
6. Go to Settings > Engaging Buttons to create your button(s), where you want the button(s) displayed, and configure other settings.


== Frequently Asked Questions ==

= Why isn't my Most Popular Posts list being updated? =

The Most Popular Posts get recalculated every 5 minutes in order to save your server resources and keep things loading quickly. Wait 5 minutes, then reload your page. The Popular Posts lists should have updated.


= Why do my buttons look strange OR Why aren't my button color changes showing up? =

Your theme's CSS is probably overly-specific. We coded the Engaging Buttons plugin to be unobtrusive, but sometimes we were *too* unobtrusive. Send us your site URL and we'll send you back some CSS fixes to add to your theme's CSS stylesheet.


= I have a word idea for a button that you haven't included. How can I add it? =

Great! Send us your ideas to katie.steiner [at] austin.utexas [dot] edu. We review the ideas with our research team to see which would be the most effective to add.


== Screenshots ==

1. A close-up of one of the Engaging Buttons styles.
2. A demo page using the Engaging Buttons and displaying the Most Respected Posts list.
3. A demo comment section using the Respect Button.
4. All the one-click button styles for you to use on your site. You can create custom styles with CSS as well.
5. In Settings > Engaging Buttons, choose which button style you want to use.
6. In Settings > Engaging Buttons, set-up one or more buttons.

== Other Notes ==

There's a shortcode and widget for displaying the most Respected/Important/etc posts/pages/comments/etc on your site. Here's instructions for how to use them.

== [engaging-posts] Shortcode ==

By default, this shortcode displays a list of 5 links to the most clicked button posts (any active post type for that button except comments) for each active button.

You can customize the output of the list by adding in a few optional parameters:

**slug="your_active_button_slug"**
Example: **[engaging-posts slug="respect"]** would output a list of the top 5 "Respected" posts (of any post type except comments).

**Accepted slug Values**
- respect
- important
- recommend
- thoughtful
- useful


**type="your_active_post_type"**
Example: **[engaging-posts slug="respect" type="comments"]** would output a list of the top 5 "Respected" comments.

**Accepted type Values**: Any active post type or comments that you have activated and have chosen in your Engaging Buttons options panel.
Common values:
- comment
- page
- post
- your_custom_post_type_slug


**how-many="2"**
Limits how many post links to display. Default is 5. Minimum is 1, maximum is 20.

**Accepted how-many Values**
- Integer from 1 - 20



== Engaging Posts Widget ==

This plugin adds an optional widget to your Appearance > Widgets page that you can use to display the pages that have the most clicks of your chosen button. It's powered by the [engaging-posts] shortcode.

To set it up:

1. Go to Appearance > Widgets from your WordPress Dashboard
2. Drag the "Engaging Posts" widget to an active widget area where you'd like it to display.
3. Enter the title
4. Choose the button you want to get the most clicked posts from (this is a dropdown that only displays your active buttons)
5. Choose the active post type you want to get the most clicked posts from.
6. Enter how many post links you want to display (minimum of 1, maximum of 20).


**NOTE: Popular Button Data is Rebuilt every 5 minutes**

Future versions may include a setting option to set this to rebuild instantly on click, or at a length of time you specify (every 1 minute, 600 minutes, etc). Right now it rebuilds every 5 minutes to save your server resources.

== Developers ==

**Developer Extensions**
To see how to query the Engaging Buttons objects or use available filters and hooks, go to our [github repository for the Engaging Buttons plugin](https://github.com/engagingnewsproject/engaging-buttons).

== Changelog ==

= Engaging Buttons 1.0.5 =
* Added long PHP start tags (<?php) for greater reliability.

= Engaging Buttons 1.0.4 =
* Engaging Buttons can be activated in PHP v5.2.

= Engaging Buttons 1.0.3 =
* Engaging Buttons now works in PHP v5.3 and greater.
* Fixed "Powered by..." text displaying under comment sections, even when that option was turned off.
* Minified all CSS files and removed CSS sourcemaps. Each file is now only ~1kb (Gzipped).
* Minified Javascript file to only ~1kb (Gzipped).
* Fixed minor Javascript warnings.
* Added Open Sans Font option to use if the buttons are not displaying quite right.
* Advanced CSS fields only show if a custom color is chosen (otherwise the field is empty)
* Advanced CSS classes are all prefixed with 'body' to give it a better chance of overriding the default CSS while still keeping best practices in mind.

= Engaging Buttons 1.0.2 =
* Color picker for customizing buttons.
* Advanced CSS output for easier overriding.

= Engaging Buttons 1.0.1 =
* Fixed svg icon not displaying on Firefox
* Moved to inline SVG for easier customization by users

= Engaging Buttons 1.0 - January 25th, 2015 =
* Initial release


== Upgrade Notice ==

= Engaging Buttons 1.0.5 =
* Plugin now works with local WAMP servers and PHP set-ups without short_open_tag enabled. This update does not bring any functionality changes.

= Engaging Buttons 1.0.4 =
* Added partial support for PHP v5.2. If you have Engaging Buttons activated on your site now, then this update should not affect anything for you.

= Engaging Buttons 1.0.3 =
* Performance improvements & PHP v5.3 support! Engaging Buttons now only adds ~2kb to your site weight (if GZIP enabled). A savings of ~500%.
* Use Open Sans Font with the buttons if they're not displaying quite right on your site.

= Engaging Buttons 1.0.2 =
* Change the color of your buttons with a simple color chooser.

= Engaging Buttons 1.0.1 =
* Fixed svg icon not displaying on Firefox

= Engaging Buttons 1.0 =
* Initial release
