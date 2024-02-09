=== Footnotes Made Easy ===
Tags: bibliography, footnotes, formatting, notes, reference, Gutenberg
Requires at least: 4.6
Tested up to: 6.4.2
Requires PHP: 7.4
Stable tag: 2.5.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Allows post authors to easily add and manage footnotes in posts.

== Description ==

Footnotes Made Easy is a simple, but powerful, method of adding footnotes into your posts and pages.

## Key features include...

* Simple footnote insertion via markup of choice (default - double parentheses)
* Gutenberg support
* Combine identical notes
* Solution for paginated posts
* Suppress Footnotes on specific page types
* Option to display ‘pretty’ tooltips using jQuery
* Lots of configuration options
* And much, much more!

### [You can try the plugin in WordPress Playground!](https://playground.wordpress.net/?plugin=footnotes-made-easy&url=%2Fwp-admin%2Fpost-new.php&wp=6.4&php-extension-bundle=kitchen-sink)

## Technical specification...

* Licensed under [GPLv2 (or later)](http://wordpress.org/about/gpl/ "GNU General Public License")
* Designed for both single and multi-site installations
* PHP7 compatible
* Fully internationalized, ready for translations. **If you would like to add a translation to this plugin then please head to our [Translating WordPress](https://translate.wordpress.org/projects/wp-plugins/footnotes-made-easy "Translating WordPress") page**

**Footnotes Made Easy is extending the [WP Footnotes](https://github.com/drzax/wp-footnotes "Github - wp-footnotes"), a plugin by Simon Elvery which was abandoned some years ago.**

👉 Please visit the [Github page](https://github.com/sdobreff/footnotes-made-easy "Github") for the latest code development, planned enhancements and known issues 👈

== Getting Started ==

Creating a footnote is incredibly simple - you just need to include your footnote in double parentheses (default), such as this...

This is a sentence ((and this is your footnote)).

You can change the markup for the footnote in the settings page of the plugin.

The footnote will then appear at the bottom of your post/page.

Or you can use a shortcode for where you want your footnotes to appear. The shortcode is "fme_show_footnotes". The shortcode also accepts a parameter of the post id in format of 'post_id=1'. If not presented, the global \WP_Post object will be used. 

You can also use a PHP call in your templates or whatever you like by using the following:
FME\Controllers\Footnotes_Formatter::show_footnotes( array( 'post_id' => 1 ) );
Note: If you choose this way (above), you have to go to the plugin settings, and set "Do not autodisplay in posts" to true.

Advanced Custom Fields (ACF) are also supported, if the ACF is installed you will see new setting in formatting page of the settings of the plugin.

== Options ==

You have a fair few options on how the identifier links, footnotes and back-links look which can be found in the WordPress admin area either on the stand alone page, or under Settings -> Footnotes - that depends on your desired setting in the plugin.

== Shortcode options ==

[fme_show_footnotes] Is the shortcode you should use. Inside the post content, there is nothing more that you have to do.
If you want to use the shortcode outside of the post content, then you need to add the post id as a parameter:
[fme_show_footnotes post_id=1]
If outside of the post content, and there is no parameter of the post id provided, then the plugin will try to use the global post if presented.

== Paginated Posts ==

Some of you seem to like paginating post, which is kind of problematic. By default each page of your post will have it's own set of footnotes at the bottom and the numbering will start again from 1 for each page.

The only way to get around this is to know how many posts are on each page and tell Footnotes Made Easy what number you want the list to start at for each of the pages. So at some point on each page (that is, between each `<!--nextpage-->` tag) you need to add a tag to let the plugin know what number the footnotes on this page should start at. The tag should look like this `<!--startnum=5-->` where "5" is the number you want the footnotes for this page to start at.

== Referencing ==

Sometimes it's useful to be able to refer to a previous footnote a second (or third, or fourth...) time. To do this, you can either simply insert the exact same text as you did the first time and the identifier should simply reference the previous note. Alternatively, if you don't want to do all that typing again, you can construct a footnote like this: `((ref:1))` and the identifier will reference the footnote with the given number.

Even though it's a little more typing, using the exact text method is much more robust. The number referencing will not work across multiple pages in a paged post (but will work within the page). Also, if you use the number referencing system you risk them identifying the incorrect footnote if you go back and insert a new footnote and forget to change the referenced number.

== Installation ==

Footnotes Made Easy can be found and installed via the Plugin menu within WordPress administration (Plugins -> Add New). Alternatively, it can be downloaded from WordPress.org and installed manually...

1. Upload the entire `footnotes-made-easy` folder to your `wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress administration.

Voila! It's ready to go.

== Frequently Asked Questions ==
= How do I create a footnote? =
Use a footnote in your post by using the footnote icon in the WordPress editor or by using a formatter character (this is " ((" for opening (beginning) of the footnote and "))" for closing the footnote)

= I've used another plugin, can I switch to this one? =
There probably be implemented some importer in the future version of the plugin, but as far as your current plugin is using opening / closing characters, you can change the opening and closing tags of the Footnotes made easy in the plugin settings to the current ones.
Example:
Lets say currently you are using plugin which marks a footnote like this:
 [[this will be a footnote]]
Then go to settings and change the Open and Close footnote tag to "[[" and "]]" respectively.

= Other than the available options, can the footnotes output be styled? =
Yes it can. The easiest way is to use the CSS editor in your theme customizer. For example, 'ol.footnotes' refers to the footnotes list in general and 'ol.footnotes li' the individual footnotes. You can edit the styling in the plugin settings directly (Options page), or empty the styling and use your own the way it fits your needs best.
CSS classes plugin is using are:
- footnotes - for the <ol>
- footnote - for the <li> elements inside
- footnotes-made-easy-header - for the footnotes header wrapper
- footnotes-made-easy-footer - for the footnotes footer wrapper

= Is there support for the Block Editor/Gutenberg Editor? =
Yes. You can use the Footnotes Made Easy button in the toolbar of the Block Editor to move the selected text into a footnote.

== Screenshots ==

1. An example showing the footnotes in use
2. The settings screen with advanced settings shown

== Change Log ==

= 2.4.2 =
* Fix: Fixed a WooCommerce bug with showing the notes in the description (thanks to [kmammasis](https://wordpress.org/support/users/kmammasis/) and [pranavmahapatra](https://wordpress.org/support/users/pranavmahapatra/))

= 2.4.0 =
* Added shortcode to display the footnotes - `fme_show_footnotes`
* Added method to display the footnotes using pure PHP way - `<?php Footnotes_Formatter::show_footnotes( array( 'post_id' => 1 ) ); ?>`
* Added filters for footnotes header and footnotes footer - `header_fme_footnotes` and `header_fme_footnotes` respectively
* Added filter for parsing the content - `fme_process_content_hooks` - passes array, just add the name of the desired hook to be attached to
* Added ACF (advanced custom fields) support from the settings

= 2.3.0 =
* Fixes bugs with the footnotes counting
* Fixes bugs with UTF-8 encoding characters in the footnotes

= 2.2.0 =
* Missing jQuery pretty tooltip option in the settings is back
* Option to use header and footer of the footnotes in the pre v2 way

= 2.0.0 =
* Security fixes
* Editors (Gutenberg and classic) integration
* Code base is rewritten
* Options are renamed
* Lots of issues are addressed and fixed
* New settings page with option to export / import the settings
* Styles can be edited from the settings page
* Quick examples in the settings page, so you get the idea of how the final result will look like
* You can search in the settings page

= 1.6.0 =
* Maintenance update - last version before 2.0.0, code fixes and updates.

= 1.0.5 =
* Maintenance update - PHP 8 compatible.

= 1.0.4 =
* Bug: Well, I messed that release up and left some test dates in place. Apologies. This fixes it all now.

= 1.0.3 =
* Maintenance: Added notices about the plugin closure

= 1.0.2 =
* Bug: Fixed some bugs around settings getting saved (thanks to [Rufus87](https://wordpress.org/support/users/rufus87/))
* Enhancement: Improved code to better meet VIP coding standards (not 100% yet but looking better!)
* Enhancement: Added Github links to plugin meta. Added other useful meta as well
* Enhancement: Minor enhancements to the way that field headings are shown in the settings
* Enhancement: Added a further check to the settings savings function, to ensure it's not called when it's not needed (thanks to [seuser](https://wordpress.org/support/users/seuser/))
* Maintenance: Increased minimum PHP level 5.6 after reports of issues at 5.4. Upgrade people!
* Maintenance: Removed some redundant code from where there used to be a button on the settings screen to reset all the options

= 1.0.1 =
* Maintenance: Updated this README to display better in the new plugin repository. Also updated the image assets (banner and icon)
* Maintenance: Minimum WordPress requirement is now 4.6. This means various checks and bits of code could be removed, including the languages folder, as this is now handled natively.

= 1.0 =
* Initial release

== Upgrade Notice ==

= 2.1.0 =
* This version of the plugin is completely rewritten, and all users must upgrade!
* Fast text parsing
* New options menu
* Gutenberg and classic editor integrations
* Fixed security vulnerabilities 