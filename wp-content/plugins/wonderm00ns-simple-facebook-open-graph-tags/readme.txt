=== Open Graph and Twitter Card Tags ===
Contributors: webdados, wonderm00n
Donate link: http://bit.ly/donate_fb_opengraph
Tags: facebook, open graph, twitter card, social media, open graph protocol, share, social, meta, rss, twitter, google, image, like, seo, search engine optimization, woocommerce, yoast seo, wordpress seo, woocommerce, subheading, php7, webdados
Requires at least: 4.5
Tested up to: 5.7
Stable tag: 3.1.1

Improve social media sharing by inserting Facebook Open Graph, Twitter Card, and SEO Meta Tags on your WordPress website pages, posts, WooCommerce products, or any other custom post type.

== Description ==

This plugin improves the sharing of your WordPress pages, posts, WooCommerce products, or any other post type on social media, by setting the correct Facebook Open Graph Tags.

It also allows you to add the Twitter Card tags for more effective and efficient Twitter sharing results, as well as the Meta Description and Canonical tags if no other SEO plugin is setting them.

**This plugin is not, in any way, affiliated or endorsed by Facebook, Twitter, Google or any other 3rd party.**

You can also choose to insert the "enclosure" and "media:content" tags to the RSS feeds, so that apps like RSS Graffiti and twitterfeed post the image to Facebook correctly.

It allows the user to choose which tags are included, and also the default image if the post/page doesn’t have one.

It’s also possible to add an overlay logo/watermark to the image. The plugin will resize and crop the original image to 1200x630 and then overlay the chosen 1200x630 PNG file over it.
It be usefull to add your brand to the image that shows up on Facebook shared links.

Our settings page is discreetly kept under "Options".

= The generated (Facebook) Open Graph Tags are: =

* **og:title**: From post/page/archive/tag/... title.
* **og:site_name**: From blog title.
* **og:url**: From the post/page permalink.
* **og:description**: From our specific custom field of the post/page, or if not set post/page excerpt if it exist, or from post/page content. From category/tag description on its pages, if it exist. From tagline, or custom text, on all the others.
* **og:image**: From our specific custom field of the post/page, or if not set from the post/page featured/thumbnail image, or if it doesn’t exist from the first image in the post content, or if it doesn’t exist from the first image on the post media gallery, or if it doesn’t exist from the default image defined on the options menu. The same image chosen here will be used and enclosure/media:content on the RSS feed.
* **og:image:url**: Same as **og:image**.
* **og:image:secure_url**: Same as **og:image** if SSL is being used.
* **og:image:width** and **og:image:height**: Image dimensions.
* **og:type**: "website" or "blog" for the homepage, "product" for WooCommerce products and "article" for all the others.
* **article:author**: From the user (post author) Faceboook Profile URL.
* **article:published_time**: Article published time (for posts only)
* **article:modified_time** and **og:updated_time**: Article modified time (for posts only)
* **article:section**: From post categories.
* **article:publisher**: The website Facebook Page URL.
* **og:locale**: From WordPress locale or chosen by the user.
* **fb:admins**: From settings on the options screen.
* **fb:app_id**: From settings on the options screen.
* **og:price:amount** and **og:price:currency**: Price on WooCommerce products.

= The generated Twitter Card Tags are: =

* **twitter:title**: Same as `og:title`.
* **twitter:url**: Sames as `og:url`.
* **twitter:description**: Same as `og:description`.
* **twitter:image**: Same as `og:image`.
* **twitter:creator**: From the user (post author) Twitter account.
* **twitter:site**: The website Twitter account.
* **twitter:card**: With value "summary_large_image" or "summary".

= Other Tags: =

* **canonical**: Same as `og:url`.
* **meta description**: Same as `og:description`.
* **meta author**: From the user (post author) Display Name.
* **meta publisher**: From the website title.
* **enclosure**: On RSS feeds, same as `og:image`.
* **media:content**: On RSS feeds, same as `og:image`.

= 3rd Party Integration: =

* **[Yoast SEO](https://wordpress.org/plugins/wordpress-seo/)**: Allows you to use the title, URL (canonical), and description from the Yoast SEO plugin.
* **[Rank Math](https://wordpress.org/plugins/seo-by-rank-math/)**: Allows you to use the title, URL (canonical), and description from the Rank Math plugin [only with the PRO add-on](https://shop.webdados.com/product/open-graph-and-twitter-card-tags-pro-add-on/)
* **[WooCommerce](https://wordpress.org/plugins/woocommerce/)**: On product pages sets `og:type` to "product" and adds the price including tax to the `product:price` and `product:availability` tags. Also allows you to use the Product Category thumbnails as Open Graph Image and have Product Gallery images as additional Open Graph Images
* **[WooCommerce Brands](https://woocommerce.com/products/brands/)**: On brand pages uses the brand image as Open Graph Image 
* **[SubHeading](https://wordpress.org/extend/plugins/subheading/)**: Add the SubHeading to the post/page title.
* **[Business Directory Plugin](https://wordpress.org/extend/plugins/business-directory-plugin/)** (deprecated): Allows you to use BDP listing contents as Open Graph Tags.

= NEW PRO add-on: =

To implement new features, we’ve released a new [PRO add-on](https://shop.webdados.com/product/open-graph-and-twitter-card-tags-pro-add-on/) that you can use alongside this plugin.

From version 3.0.0 forward, for sustainability reasons, some advanced functionalities might be removed from the free plugin and move to the PRO add-on, with the proper deprecation notice with some versions in advance.

The current PRO add-on features are:

* [Rank Math](https://wordpress.org/plugins/seo-by-rank-math/) integration: title, description and canonical
* Set different image size (instead of 1200x630)
* Fix chunked transfer encoding when using an image overlay
* Technical support (limited to the PRO add-on features)
* Good karma and support the development of new features

[Get it now for the promotional price of 20&euro;](https://shop.webdados.com/product/open-graph-and-twitter-card-tags-pro-add-on/) on the first year (and 12&euro; on renewals), or as low as 10&euro; on the first year (and 6&euro; on renewals) if you buy a license for 10 websites.

== Installation ==

1. Use the included automatic install feature on your WordPress admin panel and search for “Open Graph and Twitter Card Tags”.
2. Activate the plugin through the `Plugins` menu in WordPress
3. Go to `Options`, `Open Graph and Twitter Card Tags` to set it up

== Frequently Asked Questions ==

= Why aren’t you active on the support forums? =

Because of other commercial projects, including WordPress and WooCommerce plugins, we haven’t been able to reply to support tickets neither release new versions of this plugin, for which we are sorry.

We will fix any security issue that might arise but, at this moment, we cannot promise we’ll get back to active development and support on this plugin anytime soon.

If you reach us by email or any other direct contact means, we’ll assume you are in need of urgent, premium, and of course, paid-for support.

If some company wants to make a proposal to take ownership of this plugin, please contact us to info @ webdados .pt
We’ll not pass ownership of the plugin to anyone (person or company) that does not have a solid roadmap and business model for this plugin, to guarantee the current users that development and support will be resumed.

We also released a [PRO add-on](https://shop.webdados.com/product/open-graph-and-twitter-card-tags-pro-add-on/) with some extra features and technical support (except for the free plugin features).

= Facebook is not showing up the correct image when I share a post. What can I do? =

1. Are you using a big enough image? The minimum image size is 200x200 pixels but we recommend 1200x630.
2. Are you sure you only have one `og:image` tag on the source code? Make sure you’re not using more than one plugin to set OG tags?
3. Go to the [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/), insert your URL, click `Debug`. Then click on `Scrape Again` to make sure Facebook gets the current version of your HTML code and not a cached version. If the image that shows up on the preview (bottom of the page) is the correct one, then the tags are well set and it "should" be the one that Facebook uses when sharing the post. If it still does not use the correct image when sharing, despite the debugger shows it correctly, there’s nothing more we can do about that. That’s just Facebook being Facebook.

= What is the "Manually update Facebook cache" button on the "Post updated" notice? =

It’s a shortcut to the Facebook Sharing Debugger, where you should click on `Scrape Again` to make sure Facebook gets the current version of your post or page.

= When I save/edit my post I get the "Facebook Open Graph Tags cache NOT updated/purged" error. Should I worry? =

Each time you edit a post, if the option "Try to update Facebook Open Graph Tags cache when saving the post" is activated, we’ll try to notify Facebook of the changes so it clears up its cache and read the new Open Graph tags of this specific URL.
If this is a new post and it’s the first time you’re saving it, the error is "normal" and you should ignore it (we’re looking at a workaround to not show you this error).
If this is not a new post and it’s not the first time you’re saving it, and if this happens always, then maybe your server does not support calling remote URLs with PHP and you should disable the "Try to update Facebook Open Graph Tags cache when saving the post" option. In that scenario we recommend you use the [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/) to `Scrape Again` each time you update your post.
Sometimes the plugin just can’t update the Facebook cache itself and you may need to do it manually on the link provided above.

= Can I authenticate the call to Facebook, with my own app, when trying to update the cache, so I get rid of the "An access token is required to request this resource" error? =

Yes, you can. Create a Facebook App and fill in the details on the "Facebook Open Graph Tags cache" panel of the plugin settings page.
Do NOT ask us support on this. There is [a blog post on our website](https://www.webdados.pt/2017/12/successfully-update-facebook-cache-using-our-facebook-open-graph-plugin/) explaining everything you need to do.

= Facebook says "The following required properties are missing: fb:app_id". Should I worry? =

No. Move along.

= How can I share my posts or products as Rich Pins on Pinterest? =

Pinterest can read Open Graph tags, so no extra tags are needed to share your articles or WooCommerce products as Rich Pins.
You need however to apply to whitelist your domain on Pinterest. Head to [this page](https://developers.pinterest.com/docs/rich-pins/overview/) and follow the instructions starting with "Set up". When in the validator, enter your URL, hit "Validate" and then choose "HTML tags" and hit "Apply now". It’s then up to Pinterest to accept your application or not.

= Can this plugin get content from "random plugin"? =

If there’s a popular plugin you think we could get content from to use on the meta tags, use the support forum to tell us that.
If you are a plugin or theme author you can always use our filters `fb_og_title`, `fb_og_desc`, `fb_og_url`, `fb_og_type`, `fb_type_schema`, `fb_og_image`, `fb_og_image_additional`, `fb_og_image_overlay`, `fb_og_locale`, `fb_og_app_id`, `fb_og_thumb_fill_color`, `fb_og_output` and `fb_og_enabled` to customize the Open Graph (and other) meta tags output.

= What is the array structure for the `fb_og_image_additional` filter?

Check out this [code snippet](https://gist.github.com/webdados/ef5d5db01f01bee6041c2b2e0950d73a).

= I’m getting a white screen of death / truncated HTML =

Go to the plugin settings and check the `Do not get image size` option.
This happens on some edge cases we haven’t yet been able to identify.
Update: Probably fixed for some users on 2.1.4.5 and completely on 2.2 (pending confirmation)

= Yoast SEO shows up a big warning if both plugins are active. Is the world in danger if I keep both plugins active? =

No, it isn’t.
You can (and, in our opinion, you should) use both plugins. If you want to use Yoast SEO for your SEO needs and our plugin for social media meta tags you just have to go to "SEO > Social" and disable settings for Facebook and Twitter.
If you don’t find that option, because they’ve now made it harder to reach, you have to go to "SEO > Dashboard > Features > Advanced settings pages > choose Enabled and Save changes". Then you can reach "SEO > Social".
Then set up our plugin as you wish and you’re ready to go.
We like to work with everybody, so (if you want to) our plugin can even integrate with Yoast SEO and use it’s title, description and canonical URL on the Facebook and Twitter tags.

= There’s a similar plugin on the repository, by Heateor. Is this the same? =

It’s similar, yes. They’ve forked our plugin and gave no credits whatsoever for our original work.

== Changelog ==

= 3.1.1 =
* Added i18n-config.json file for basic [qTranslate-XT](https://github.com/qtranslate/qtranslate-xt) compatibility (Thanks @grapestain)
* Tested with WordPress 5.7-alpha-50017 and WooCommerce 5.0.0-beta.2

= 3.1.0 =
* Add support for new PRO add-on features
* Tested with WordPress 5.6-beta3-49562 and WooCommerce 4.8.0-beta.1

= 3.0.0 =
* Add support for the PRO add-on
* Deprecation of Business Directory Plugin integration and other minor settings
* readme.txt full review

= 2.3.3 =
* Technical support clarification
* Tested with WordPress 5.5-alpha-47761 and WooCommerce 4.1.0

= 2.3.2 =
* PHP notice bug fix on the image overlay script

= 2.3.1 =
* PHP notice bug fix 

= 2.3.0 =
* Option to disable image overlay on the default image
* New argument to the `fb_og_image_overlay` with queried object information
* When outputting the composed image with overlay in JPG the quality is now 100 instead of 95 and the `fb_og_overlayed_image_format_jpg_quality` is available to change it
* Compatibility with Yoast SEO 14

= 2.2.8 =
* New feature to shrink and center the original image when overlay is used
* Google+ / Schema feature deprecation
* New fb_og_thumb developer filter to allow the manipulation of the original thumbnail image on the overlay php script before the logo is applied on top of it
* The current object type and object id being queried is passed on to the image overlay php script so it can be used by developers on the new filter
* $_GET fix on the image overlay php script
* Tested with WordPress 5.5-alpha-47609 and WooCommerce 4.1.0-rc.1

= 2.2.7.2 =
* Tested with WordPress 5.2.5-alpha and WooCommerce 3.8.0

= 2.2.7.1 =
* Changed name to “Facebook Open Graph, Google+ and Twitter Card Tags” due to Facebook intellectual property and brand violation policies

= 2.2.7 =
* New developer filter `fb_og_overlayed_image_format` to be able to output the overlayed image as png instead of jpg
* Tested with WordPress 5.2.3-RC1-45880 and WooCommerce 3.7.0

= 2.2.6.1 =
* Stop using the WooCommerce term meta helper functions

= 2.2.6 =
* Add `og:image:url` and `og:image:secure_url` tags
* Small tweaks on the image overlay functionality, including a new `fb_og_thumb_image` filter so you can have a diferent image overlay based on the post id
* Tested with WordPress 5.1.1 and WooCommerce 3.6.0 (RC1)

= 2.2.5 =
* No `og:description` for password protected and private posts (Thanks for the heads up Benoît)
* Tested with WordPress 5.0.3 and WooCommerce 3.5.3

= 2.2.4.2 =
* Small security fix

= 2.2.4.1 =
* Small security fix

= 2.2.4 =
* Added Pinterest information on que FAQ
* Changed name to "Facebook Open Graph, Google+ and Twitter Card Tags"

= 2.2.3.1 =
* Tested with WooCommerce 3.3
* Improved readme.txt

= 2.2.3 =
* Small bug fix introduced in 2.2 that would throw a PHP notice if no tool was selected when saving the settings (Thanks @osti47)
* Clear image size cache (transients) on uninstall
* Better debug for support purposes, which can be disabled by returning false on the `fb_og_enable_debug` filter
* Bumped `Tested up to` tag

= 2.2.2 =
* NEW WooCommerce Brands integration: Uses the Brand thumbnail image if the "Use Category thumbnail as Image" option is enabled

= 2.2.1 =
* Added the Schema.org headline, author, datePublished and dateModified tags
* Removed some unnecessary / duplicated `esc_attr` calls

= 2.2 =
* New "Tools" panel on the settings page
* Tool to clear all the plugin transients, thus resetting size caching for all images (use it only )
* Small bug fix when the settings aren't yet saved at least one time
* When getting the image size, the full image is used again, instead of the partial 32Kb (that caused WSOD in some environments), but this can be overridden (and use the partial again) by returning false to the new `fb_og_image_size_use_partial` filter
* Transient validity is now one week (now that we get the all image and the process can slow down the page load a bit), instead of one day
* Fix when getting the image and description for the blog url when it's set as a page (Thanks @alexiswilke)
* Ability to disable image size cache (transients) completely by returning false to the new filter `fb_og_image_size_cache` (which we do NOT recommend)
* Improved the FAQ

= 2.1.6.3 =
* Fix the "Suppress cache notices" option (Thanks @digbymaass)

= 2.1.6.2 =
* Default `og:type` to `website` on non-singular pages because Facebook deprecated it (Thanks @alexiswilke)
* Automatically disable the overlay feature if GD is not installed (Thanks @tiagomagalhaes)
* Set `og:type` to `website` instead of `article` when a page is set as a blog page
* Use image from the page (our custom field, featured, etc...) when a page is set as a blog page (Thanks @alexiswilke)
* Use description from our custom field when a page is set as a blog page
* Adds the `product:availability` tag for WooCommerce products (Thanks @steveokk)
* Improved the FAQ

= 2.1.6.1 =
* Updated the error message when the Facebook cache is not updated, in order to include instructions and a link to setup the App ID and Secret

= 2.1.6 =
* Added new "App ID" and "App Secret" settings on the "Facebook Open Graph Tags cache" panel, so that the plugin tries to clear the Facebook cache authenticated with you own App details
* Bumped `Tested up to` and `WC tested up to` tags
* Improved the FAQ

= 2.1.5 =
* Stop showing the metabox or trying to update Facebook cache on non-publicly_queryable post types

= 2.1.4.5 =
* Set `CURLOPT_FOLLOWLOCATION` to `true` when trying to get image size via curl and avoid fatal errors (white screen of death) when the response returns 301 or 302 - Thanks [@neonkowy](https://wordpress.org/support/users/neonkowy/)

= 2.1.4.4 =
* Added the `fb_og_update_cache_url` filter so that developers can add their Facebook App ID and Secret to the URL when trying to update/clear cache on Facebook - Thanks [@l3lackcat](https://profiles.wordpress.org/amirullahmn)

= 2.1.4.3 =
* Added a "Share this on Facebook" button to the "Post updated" notice
* Fixed some URLs and links from http:// to https://
* Removed the option to load Facebook locales from their website as the URL now returns 404

= 2.1.4.2 =
* Added a "Manually update Facebook cache" button to the "Post updated" notice
* Improved the FAQ

= 2.1.4.1 =
* Better information when showing up the "Facebook Open Graph Tags cache NOT updated/purged" error, as well as a link to update the cache manually
* Improved the FAQ

= 2.1.4 =
* Changed the way the admin notices are generated so we do not have to use PHP sessions

= 2.1.3 =
* Fixed some PHP notices and warnings
* Tested with WooCommerce 3.2
* Added `WC tested up to` tag on the plugin main file
* Bumped `Tested up to` tag

= 2.1.2 =
* Fix the fact that we are using `sanitize_textarea_field()` that only exists since 4.7.0 although we had a 4.0 minimum requirement (Thanks @l3lackcat)
* Fix the textdomain on a couple of gettext calls
* Bumped the `Requires at least tag` to 4.5 (to encourage the ecosystem to have WordPress updated)
* Added code snippet example for the `fb_og_image_additional` filter

= 2.1.1 =
* Fix on the gettext calls and textdomain so that Glotpress correctly loads translations

= 2.1 =
* New description field on our metabox on post/pages that will override the excerpt or content if filled
* Load the translations from wordpress.org Glotpress and not from the local folder
* Added the `fb_og_app_id` filter so that plugins or themes can override the Open Graph Facebook App ID

= 2.0.9 =
* New option to disable getting the image size and possibly avoid fatal errors (white screen of death) on some edge cases
* New `fb_og_disable` filter to completely disable the output based on the developer own rules - DUPLICATE - Use `fb_og_enabled` instead and return false to it.

= 2.0.8.2 =
* New `fb_og_output` filter on the plugin global output

= 2.0.8.1 =
* Using `get_gallery_image_ids` instead of `get_gallery_attachment_ids`, to get additional WooCommerce product images, from WooCommerce 3.0 up (Thanks Manu Calapez)

= 2.0.8 =
* Tried to fix the white screen of death problem in some server environments
* New option to use "prefix" attribute instead of "xmlns" (Thanks thomasbachem)
* Tested and adapted to work with WooCommerce 3.0.0-rc.2
* Fixed the WooCommerce [product price tag name](https://developers.facebook.com/docs/reference/opengraph/object-type/product/) from `og:` to `product:` (Thanks davidtrebacz)
* Fixed a bug on the database version update mechanism
* Fixed a bug for custom taxonomy descriptions (Thanks karex)
* Bumped `Tested up to` tag

= 2.0.7 =
* Better error handling when the overlay PNG image is not found or a valid image file;

= 2.0.6.3 =
* When using the overlay PNG option, the image is filled with previously filled with white in case the original OG image is a transparent PNG
* The `fb_og_thumb_fill_color` filter can be used to use another color other than white, by returning an array with the rgb value

= 2.0.6.2 =
* On some server configurations using an overlay PNG would result on a 404 error on the `og:image` url

= 2.0.6.1 =
* Bumped `Tested up to` tag

= 2.0.6 =
* New `fb_og_enabled` filter that allow other plugins/themes to completely disable the tags output on specific situations
* Fix: Use `WP_PLUGIN_DIR` instead of harcoded paths (Thanks thomasbachem)
* Fix: Extra validation when getting Yoast SEO's icon on the 3rd party settings tab

= 2.0.5 =
* Calculated image dimensions are now stored in, 1 day valid, transients in order to avoid unnecessary http requests and improve performance (Thanks Piotr Bogdan)

= 2.0.4 =
* Non-breaking spaces are converted to normal spaces on the description, because they aren't needed there and this way we can really trim the description 

= 2.0.3 =
* Fixed WPML homepage custom description translation that was broken on 2.0
* New default description field (WPML translatable) to be used on any website post / page / cpt / archive / search / ... that has an empty description, instead of using the homepage description like we were using until now
* Fixed some PHP notices and warnings
* Small tweaks on the settings page

= 2.0.2 =
* Fixed a PHP Notice on WPML root pages (Thanks @marcobecker)
* New experimental feature: Added the possibility to set `itemscope itemtype` to the HTML Tag in order to avoid W3C and Structured Data validation errors
* Updated settings page informations to include the existent filters for each option

= 2.0.1 =
* Fixed a PHP Notice on the upgrade routine (Thanks @jluisfreitas)
* Updated FAQs

= 2.0 =
* We would like to thank Heateor, "a creative team with unique ideas in mind" (their words), for forking our plugin (although no credits whatsoever were made regarding our original work), and thus trully inspiring us to make this new version, using also their "unique" ideas, in the spirit of GPL, but giving them the deserved credit in the spirit of civism, integrity and the WordPress way of doing things
* Revised and optimized code with better WordPress standards and best practices
* Completely redesigned settings screen
* Fixed Business Directory Plugin integration (only works with CPT integration activated)
* Removed Google authorship (link rel="author") tag because **[it isn't used anymore](https://support.google.com/webmasters/answer/6083347)**
* Added meta `publisher` tag
* New option to either keep or delete plugin configurations upon uninstall
* Fixed a bug where a custom taxonomy description was not being correctly set
* Fixed `og:price:amount` and `og:price:currency` tags now correctly set as `property` instead of `name`

= 1.7.4.4 =
* Bug fix: WooCommerce price integration wasn't working on WooCommerce >= 2.6 (Thanks @jluisfreitas)
* Bumped `Tested up to` tag

= 1.7.4.3 =
* Version number fix
* Portuguese translation update

= 1.7.4.2 =
* Overlay PNG will only be used on locally hosted images
* New `fb_og_image_overlay` filter to be able to disable Overlay PNG programatically based on whatever needs of the developer
* Better texts about the Overlay PNG function on the settings page
* Minor fixes

= 1.7.4.1 =
* WooCommerce integration: on product pages sets `og:type` to "product" and adds the price including tax to the `product:price` tags

= 1.7.4 =
* New experimental feature: Add overlay PNG logo to the image
* Minor fixes

= 1.7.3.1 =
* Fix: changed `twitter:image:src` meta name to `twitter:image` (Thanks mahler83)
* Tested with WordPres 4.5 RC2

= 1.7.3 =
* Avoid php notice on the "Members" plugin role edit page

= 1.7.2 =
* Better SubHeading plugin compatibility (choose either to add it Before or After the title)
* Description set the same value as the title if it's empty
* Correct `og:type` for WPML root pages
* Added the `fb_og_type` filter so that plugins or themes can override the Open Graph Type

= 1.7.1 =
* Added the `fb_og_url` filter so that plugins or themes can override the Open Graph URL

= 1.7 =
* WordPress 4.4, WooCommerce 2.4.12 and PHP 7 compatibility check - All good!
* NEW WooCommerce integration: Category thumbnail images and additional product images as Open Graph Images
* New `fb_og_image_additional` filter so other plugins/themes can add additional Open Graph Images
* Hide our meta box on some custom post types, and add a new `fb_og_metabox_exclude_types` filter so other plugins/themes can hide our metabox on their CPTs
* Several tweaks on the settings page

= 1.6.3 =
* Added the `fb_og_locale` filter so that plugins or themes can override the Open Graph locale tag

= 1.6.2.2 =
* Bug fix: Google+, Twitter and Facebook profile fields would not be available on the user profile if Yoast SEO was not active

= 1.6.2.1 =
* Fix: Eliminates php notices introduced on 1.6.2

= 1.6.2 =
- Fix: Replaces all spaces by %20 on URLs (`og:url`, `og:image`, etc...), thanks to "Doc75"

= 1.6.1 =
* WPML compatibility: If the frontpage is set as "latest posts" and a custom homepage description is used, it can now be translated to other languages in WPML - String translation

= 1.6 =
* Added `og:image:width` and `og:image:height` tags if Facebook is having problems loading the image when the post is shared for the first time
* Added the possibility to choose the Twitter Card Type
* It's now possible to hide the author tags on pages
* Fix: SubHeading plugin was not found on multisite
* Fix: On the image attachment pages the `og:image` tag was not correctly set
* Fix: Several PHP notices fixed
* Updated FacebookLocales.xml

= 1.5.2 =
* Fix: Fatal error integrating with WPSEO's last version
* Fix: Checking for post_type when saving the meta box field and updating/purging Facebook Open Graph Tags cache to avoid doing it when unnecessary 

= 1.5.1 =
* Fix: error checking and reporting when updating/purging Facebook Open Graph Tags cache to avoid fatal errors on failure
* Fix: change Facebook cache update call from https to http to avoid errors on some server configurations
* Updated FacebookLocales.xml

= 1.5 =
* Each time a post/page is saved we try to update/purge Facebook Open Graph Tags cache so you can share it right away from the post edit screen
* Forced Excertps support on Pages so that it can be used as the post description
* Added HTTP REFERER and USER AGENT to the cURL calls when trying to get image size by url
* Fix: Some validations when trying to get image size by url
* Fix: A lot of php notices and warnings were supressed

= 1.4.2 =
* Fix: (Another) debug message removed

= 1.4.1 =
* Fix: Debug message removed

= 1.4 =
* Added article published and modified date/time tags
* Added "article:section" tag
* Several fixes regarding getting the content or media gallery image size
* Several fixes on the way the defaults and settings are loaded to avoid php warnings and notices
* Some changes on the default values for first time users

= 1.3.4 =
* Fix getting image size when a remote image is used on the post content (thanks contemplate and Steve)
* Change the way the default settings are load so that even settings that are not user defined will be available (like the new image minimum size which is, for now, "hardcoded")

= 1.3.3 =
* Fix where servers with allow_url_fopen disabled would not be able to get_image_size for post content or media gallery images (thanks joneiseman)

= 1.3.2 =
* Added Google+ Publisher tag
* Fix on some Portuguese translation strings

= 1.3.1 =
* Ignore images bellow 200x200 when searching images from the post content and media gallery, because Facebook also ignores them

= 1.3 =
* Changed name to "Facebook Open Graph, Google+ and Twitter Card Tags"
* Added Twitter Card tags
* Added new tag "article:publisher" in order to link the article with a Facebook page
* Added new tags "article:author", meta author and Google+ link rel in order to link the article with his author (Facebook profile, Name and Google+ profile)
* Title, URL, Description and Image Meta/Google+/Twitter tags can now be set even if Open Graph ones aren't
* Several HTML/CSS tweaks on the settings page
* Fix: esc_attr on all tags

= 1.2 =
* Added filters for title, description and images, so another plugin or theme can override these values. The filters are `fb_og_title`, `fb_og_desc` and `fb_og_image`

= 1.1.2 =
* Fix: Specific post image was not working properly
* Added a "Clear field" button to the specific post image options box
* When the homepage is set as a static page, the "homepage description" section on the settings page will reflect that

= 1.1.1 =
* Fix: a debug var_dump was left uncommented
* readme.txt adjustments

= 1.1 =

* WordPress SEO by Yoast, now Yoast SEO, integration: title, url (canonical) and description can now be fetched from this very popular SEO plugin
* Fix: small fix on javascript

= 1.0.1 =

* Corrected a nasty bug which would break the "Add Media" option. Thanks to @flynsarmy (yet again)
* Fix: version field upgrade on the database

= 1.0 =

* Plugin name changed from "Wonderm00n's Simple Facebook Open Graph Meta Tags" to "Facebook Open Graph Meta Tags for WordPress"
* You can now set a specific Open Graph image per post, if you don't want it to be the same as the post featured image
* Settings are now stored on a array instead of multiple variables on the options table (and necessary changes along the code)
* Internationalization support added
* Portuguese translation added (we welcome other translations if you want to do it)
* Added webdados as contributor (Wonderm00n's company)
* Fix: Several PHP warnings when WP_DEBUG is turned on. Thanks to @flynsarmy (yet again)
* Fix: `og:type` was not set correctly for the homepage in case it was a static page. Thanks to yakitori
* Fix: When the site url was not the same as the WordPress installation folder the wrong url was used in the homepage `og:url`/`canonical` tag. Thanks to theonetruebix
* Using the requested url as `og:url`/`canonical` on not known areas of WordPress. Not really a canonical url but better than using the homepage one

= 0.5.4 =

* Fix in order to be compatible with "Business Directory Plugin" 3.0

= 0.5.3 =

* Minor fix to avoid php notices filling up error logs. Thanks to @flynsarmy (yet again).

= 0.5.2.1 =

* Fixed version number.

= 0.5.2 =

* Minor fix to avoid php notices filling up error logs. Thanks to @flynsarmy (again).
* Fixed FacebookLocales.xml URL.
* By default the FacebookLocales.xml is loaded from the local cache (to save on bandwidth) and it's only loaded from Facebook URL by user request.
* Deleted some commented debug stuff and translate portuguese comments to english.

= 0.5.1 =

* Fixed a typo.
* Added the information about the recommended minimum image size.

= 0.5 =

* Added meta description and Schema.org name, description and image tags.

= 0.4.3 =

* Fixed a bug where the original, WordPress stock, Canonical URL was not being removed.

= 0.4.2 =

* If using the "Business Directory Plugin" integration, the `og:url` tag is now correctly set in the category listing pages.

= 0.4.1 =

* Added the ability to set/replace the Canonical URL tag. Very important for SEO in the "Business Directory Plugin" integration.

= 0.4 =

* "Business Directory Plugin" plugin integration. It's now possible to populate `og:title`, `og:url`, `og:description` and `og:image` tags with each listing details. If a featured image is set it will be used. If not, the listing main image is used.

= 0.3.5 =

* Minor fixes to avoid php notices filling up error logs. Thanks to @flynsarmy.

= 0.3.4 =

* Fixed a bug where all the settings could be lost when saving other plugins options (Shame on me!!).

= 0.3.3 =

* Fixed a bug where unset options would become active again. Thanks to @scrumpit.

= 0.3.2 =

* Fixed a typo on the settings page.

= 0.3.1 =

* When saving the settings the $_POST array was showned for debug/development reasons. This output has been removed.

= 0.3 =

* "SubHeading" plugin integration. It's now possible add this field to the `og:title` tag.
* Changed the way defaults and user settings are loaded and saved, to "try" to eliminate the problem some users experienced where the user settings would disappear.
* Bugfix: "Also add image to RSS/RSS2 feeds?" option was not being correctly loaded.
* The plugin version is now showed both as a comment before the open graph tags and on the settings page.

= 0.2.3 =

* No changes. Had a problem updating to 0.2.2 on the WordPress website.

= 0.2.2 =

* Bugfix: small change to avoid using the "has_cap" function (deprecated). Thanks to @flynsarmy.

= 0.2.1 =

* Bugfix: when the `og:image` is not hosted on the same domain as the website/blog.

= 0.2 =

* If the option is set to true, the same image obtained to the `og:image` will be added to the RSS feed on the `enclosure` and `media:content` tags so that apps like RSS Graffiti and twitterfeed post them correctly.

= 0.1.9.5 =

* It's now possible to choose how the post/page `og:image` tag is set. It means that if the user doesn't want to use the featured/thumbnail image, or the first image in the post content, or the first image on the media gallery, or even the default image, he can choose not to.

= 0.1.9 =

* Added the `og:locale` tag. This will be the WordPress locale by default, but can be chosen by the user also.
* The `og:type` tag can now be set as "website" or "blog" for the homepage.
* A final trailing slash can now be added to the homepage url, if the user wants to. Avoids 'circular reference error' on the Facebook debugger.


= 0.1.8.1 =

* Fixed the namespace declarations.

= 0.1.8 =

* Type "website" was being used as default for all the urls beside posts. This is wrong. According to Facebook Open Graph specification only the homepage should be "website" and all the other contents must bu "article". This was fixed.
* On Category and Tags pages, their descriptions, if not blank, are used for the `og:description` tag.
* If the description comes out empty, the title is used on this tag.

= 0.1.7 =

* Changed the plugin priority, so that it shows up as late as possible on the <head> tag, and it won't be override by another plugin's Open Graph implementation, because other plugins usually don't allow to disable the tags. If you want to keep a specific tag from another plugin, you can just disable that tag on this plugin options.

= 0.1.6 =

* Settings link now shows up on the plugins list.
* Small fix to ensure admin functions only are running when on the admin interface.
* Some admin options now only show up when the tag is set to be included.


= 0.1.5 =

* Fixed the way Categories and Tags pages links were being retrieved that would cause an error on WP 3.0.
* Added the option to use a Custom text as homepage `og:description` instead of the Website Tagline.
* Fixed a bug that wouldn't allow to uncheck the `og:image` tag.

= 0.1.4 =

* Shortcodes are now stripped from `og:description`.
* Changed `og:app_id` and `og:admins` not to be included by default.

= 0.1.3 =

* Just fixing some typos.

= 0.1.2 =

* Fixing a bug for themes that do not support post thumbnail.

= 0.1.1 =

* Adding Open Graph Namespace to the HTML tag.

= 0.1 =

* First release.