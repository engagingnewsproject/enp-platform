=== WP Engine Automated Migration ===
Contributors: wpengine, blogvault, akshatc, taylor4484
Tags: wpe, wpengine, migration
Requires at least: 4.0
Tested up to: 5.6
Requires PHP: 5.4.0
Stable tag: 4.35
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

WP Engine Automated Migrations allows you to easily migrate your WordPress site to the WP Engine platform. All you need to do is provide the plugin your WP Engine SFTP credentials and let the tool do all the heavy lifting.

For full instructions and solutions to common errors, please visit our [WP Engine Automated Migrations](http://wpengine.com/support/wp-engine-automatic-migration/) support garage article.

= Developers =

* This tool will provide you an quick way to migrate sites, allowing you to work on your other projects while the plugin does all the heavy lifting.
* No need to worry about searching/replacing values in the database, the plugin does it for you.
* You can migrate multiple sites easily.

= Marketers =

* Worried about SEO? The plugin will keep all your links the same, making sure you do not lose any SEO.
* Minimal technical knowledge needed to migrate your site to WP Engine.

= Site Owners =

* Minimal technical knowledge needed to migrate your site to WP Engine.
* Focus on your business instead of migrating your site.
* No need to hire a third party migration team.

= Legal Requirements =

* By using this plugin, you are agreeing to our [Terms of Service](http://wpengine.com/terms-of-service/)

= * Please Note * =

This plugin will only migrate your site to [WP Engine](http://wpengine.com/). This will not migrate you to any other host.

== Installation ==

1. Upload `wp-migrate-site` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

If you have WordPress 2.7 or above you can simply go to 'Plugins' > 'Add New' in the WordPress admin and search for "WP Engine Migration Tool" and install/activate it from there.

== Frequently Asked Questions ==

1) What information do I need to provide to the plugin?

You will have to provide the plugin your email address, destination url, SFTP host name, SFTP username, and SFTP password.

2) Does this plugin work with Multisite?

Yes! This has been fully developed to work with Multisite. If moving a Multisite, make sure to Network Activate the plugin.

3) Can I migrate a single WordPress install into a Multisite using this plugin?

No. This plugin is designed to migrate sites as is. This plugin will not support single WordPress sites being migrated into a Multisite or a Multisite sub-site being migrated to a single WordPress site.

4) Are their any known incompatibilities?

Currently, you can not migrate a site from WordPress.com or any proprietary hosting solution. (Squarespace.com, Wix.com, and similar hosting providers)

5) Other than running the plugin, anything else I need to do?

Once the migration completes, you will need to update your DNS to point to our servers. Keep in mind that you may also need to add any custom redirects to your WP Engine User Portal and migrate your SSL.

6) How do I sign up for a WP Engine Account?

That's easy! [Signup here](http://wpengine.com/plans/).

== Screenshots ==

1. Adding information to the WP Engine Migration Tool in the WP-Admin.
2. WP Engine User Portal Migration Page, https://my.wpengine.com
2. BlogVault dashboard showing live updates.

== Changelog ==
= 4.35 =
* Improved scanfiles and filelist api

= 4.31 =
* Fetching Mysql Version
* Robust data fetch APIs
* Core plugin changes
* Sanitizing incoming params

= 3.9 =
* .htaccess Warning Added in Main Page

= 3.4 =
* Plugin branding fixes

= 3.2 =
* Updating account authentication struture

= 2.3 =
* Adding params validation
* Adding support for custom user tables

= 2.1 =
* Restructuring classes

= 1.88 =
* Callback improvements

= 1.86 =
* Updating tested upto 5.1

= 1.84 =
* Disable form on submit

= 1.82 =
* Updating tested upto 5.0

= 1.77 =
* Adding function_exists for getmyuid and get_current_user functions 

= 1.76 =
* Removing create_funtion for PHP 7.2 compatibility

= 1.72 =
* Adding Misc Callback

= 1.71 =
* Adding logout functionality in the plugin

= 1.69 =
* Adding support for chunked base64 encoding

= 1.68 =
* Updating upload rows

= 1.66 =
* Updating TOS and privacy policies

= 1.64 =
* Bug fixes for lp and fw

= 1.62 =
* SSL support in plugin for API calls
* Adding support for plugin branding

= 1.44 =
* Removed bv_manage_site
* Updated asym_key

= 1.41 =
* Better integrity checking
* Woo Commerce Dynamic sync support

= 1.40 =
* Manage sites straight from BlogVault dashboard

= 1.31 =
* Changing dynamic backups to be pull-based

= 1.30 =
* Using dbsig based authentication

= 1.22 =
* Adding support for GLOB based directory listings

= 1.21 =
* Adding support for PHP 5 style constructors

= 1.20 =
* Adding DB Signature and Server Signature to uniquely identify a site
* Adding the stats api to the WordPress Backup plugin.
* Sending tablename/rcount as part of the callback
* Updated UI and added helpful content to provide a better migration experience.

= 1.17 =
* Add support for repair table so that the backup plugin itself can be used to repair tables without needing PHPMyAdmin access
* Making the plugin to be available network wide.
* Adding support for 401 Auth checks on the source or destination

= 1.16 =
* Improving the Base64 Decode functionality so that it is extensible for any parameter in the future and backups can be completed for any site
* Separating out callbacks gettablecreate and getrowscount to make the backups more modular
* The plugin will now automatically ping the server once a day. This will ensure that we know if we are not doing the backup of a site where the plugin is activated.
* Use SHA1 for authentication instead of MD5

= 1.15 =
* First release of WP Engine Plugin
