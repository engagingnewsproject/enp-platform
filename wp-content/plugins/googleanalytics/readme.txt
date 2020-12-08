=== Google Analytics ===
Contributors: sharethis, scottstorebloom
Tags: analytics, google analytics, google analytics plugin, google analytics widget, google analytics dashboard
Requires at least: 3.8
Tested up to: 5.5.1
Stable tag: 2.4.1

Use Google Analytics on your Wordpress site without touching any code, and view visitor reports right in your Wordpress admin dashboard!

== Description ==

Monitor, analyze, and measure visitor engagement for your site directly from your WordPress dashboard with our Google Analytics plugin. With our Google Analytics dashboard, you’ll be able to conveniently access Google Analytics reports in the same interface you already use every day to write and manage your posts.

Unlike other plugins, there are no monthly fees or paid upgrades for this plugin. All of the features are 100% free.

**GOOGLE ANALYTICS SETUP**

Get started in less than 10 minutes. Installation is quick and easy, no developers required.

Simply download the plugin, log into Google, select the required website, and it will automatically include the appropriate code.

**GOOGLE ANALYTICS DASHBOARD**

Start making data-driven decisions with real time stats including:

* Visitor trends – Dive deeper into your website’s page views, users, pages per session, and bounce rate for the past 7 days as compared to the previous 7 days
* Traffic sources – Discover which 5 traffic sources are driving the most visitors to your website
* Top pages – Stay updated on your 10 most viewed pages
* New! Demographics - Get age and gender data in your dashboard.
* New! GDPR Compliance Tool - For all your EU Compliance, we've integrated the ShareThis GDPR Compliance Tool into our plugin!

With our mobile-optimized plugin, you’ll be able to stay informed and get actionable insights on any device. For more accurate stats, you also have the option to disable tracking for any role like Admins or Editors so your analytics represent real visitors.

If you don’t have a Google Analytics account, you can sign up for free here: [https://www.google.com/analytics/](https://www.google.com/analytics/)

**LOOKING FOR MORE WAYS TO GROW YOUR WEBSITE?**

[Share buttons](https://wordpress.org/plugins/sharethis-share-buttons/) - Enable one-click sharing to start scaling your site traffic today.

[Follow buttons](https://wordpress.org/plugins/sharethis-follow-buttons/) - Expand your social following across 20+ social channels including Facebook, Twitter, WhatsApp, and Pinterest.

[Reaction buttons](https://wordpress.org/plugins/sharethis-reaction-buttons/) - Give your audience a fun and easy way to react to your content.


**SUPPORT**

If you have any questions, please contact us at [support@sharethis.com](mailto:support@sharethis.com).

By downloading and installing this plugin, you are agreeing to the [Privacy Policy](http://www.sharethis.com/privacy/) and [Terms of Service](http://www.sharethis.com/publisher-terms-of-use/).


== Installation ==

1. Install Google Analytics either via WordPress.org plugin repository or directly by uploading the files to your server
2. Activate the plugin through the Plugins menu in your WordPress dashboard
3. Navigate to Google Analytics in the WordPress sidebar
4. Authenticate via Google, copy and paste the access code and choose your property from the dropdown. You can also add the web property ID from Google Analytics manually but dashboards won't show up in this case.
5. When any of your content takes off you will see the URLs inside the Trending Content section

== Frequently Asked Questions ==
= Is this plugin compatible with WPML? =
Yes! We've made adjustments to allow for WPML pages to be tracked properly.

= Why do I need an SSL certificate to use this plugin? =
Since the plugin accesses your Google Analytics account your login information is transerfered from our plugin to google.  This needs to be secure so SSL is required to keep your information safe.

= Do I need to touch any code to add Google Analytics? =
Nope, just sign in with google, choose your website, and our plugin will automatically add Google analytics code to all pages.

= How do I make sure Google Analytics is properly installed on all pages? =
If you signed it with google and selected your website (or manually added the property ID) the Google Analytics javascript will be added to all pages. To check what UA code it is adding, just open any page of your website in Chrome, right click to select Inspect, navigate to Network tab, reload the page and search for googleanalytics, you will see the google code with your UA ID. <a href=”https://cl.ly/1q3o2q26261V/[e5b08a5ae1c09684a56ba14c36e6fa5c]_Screen%2520Shot%25202017-02-06%2520at%25201.57.34%2520PM.png” title=”Google Analytics code on the page example”>See example here.</a>

= I see broken formatting inside the plugin, for example some buttons are not aligned? =
This is likely caused by AdBlocker that is blocking anything related to "google analytics". Please disable AdBlocker for your own website or add it to exceptions if you are using Opera.

= How does that cool "Trending Content" feature work? =
It learns about your traffic patterns to spot "spikes" of visitors and then sends an alert. If your website doesn't have good amount of visitors you might not see any Trending Content Alerts because the algorithm needs more data to see "trends".

= I have other questions, where I can get support or provide feedback? =
If you have any questions please let us know directly at support@sharethis.com or create a new ticket within our WP support portal.
We are always happy to help.

== Screenshots ==

1. Overall site performance - the past 7/30 days
2. The top 10 page views for the past 7/30 days
3. Directly authenticate Google Analytics, and exclude sets of logged in users
4. Just click to authenticate, then copy the API key and add it to the plugin

== Changelog ==

= 2.4.1 =
* Fix admin error.

= 2.4.0 =
* Add GDPR compliance tool integration.
* Add Demographic data chart option.
* Fix ST terms agreement.

= 2.3.8 =
* Fix compatibility with WPML.

= 2.3.7 =
* Fix property creation structure.
* Remove terms blocker.

= 2.3.6 =
* Add onboarding product to property creation.

= 2.3.5 =
* Updated analytics feature.
* Add filter to show 30 days worth of data.
* Add “Top 10 Pages/Posts” by Pageviews.
* For all charts, add a link that allows user to go to Google Analytics page where the data is from.
* Improved debug messaging.
* Removed “Trending Contents” feature.
* Removed comparison line in chart.

= 2.2.5 =
* WP ver 5+ compatibility tests.
* Code quality clean up.
* Fix setting save.

= 2.1.5 =
* Added IP Anonymization Option.
* Added Google Optimization field.
* Updated GA code posting method.

= 2.1.4 =
* Updated SSL cert reference.
* Fixed Trending Content connection issue.
* Updated copy for better user understanding.
* Removed auto sending function from debug / added copy function.

= 2.1.2 =
* Fixed authentication error issue experienced by some users.
* Added re-authentication button for easier changing or relinking of Google Analytics accounts.
* Added “Send Debug” button for faster technical troubleshooting.
* Added refresh button for Google Analytics within dashboard.
* Included new alert for missing Google Analytics account.
* Included new alert for unsupported PHP version.

= 2.1.1 =
* Reduced requests to Google API to help with Google Analytics quotas

= 2.1 =
* NEW: Trending Content - trending content shows you a list of content that is performing better than average
* NEW: Alerts - option to sign up for alerts via email or Slack when your content is taking off
* Additional caching to always show Google Analytics dashboards
* User interface improvements

= 2.0.5 =
* Better compatibility with the Google API quotas
* Undefined variable fix, thanks to charlesstpierre

= 2.0.4 =
* Replaced Bootstrap with own scripts

= 2.0.3 =
* Reliability improvements for Google Analytics access
* Better connection to Google Analytics API
* Fixed the save settings issue, thanks @biologix @tanshaydar
* Minor bug fixes

= 2.0.2 =
* Fixed issues related to older versions of PHP
* Fixed terms of service notice
* Added better support for HTTP proxy, thanks @usrlocaldick for the suggestion
* Added better support when WP_PLUGIN_DIR are already set, thanks @heiglandreas for the tip
* Added support for PHP version 5.2.17

= 2.0.1 =
* Fix for old versions of PHP

= 2.0.0 =
* Completely redesigned with new features!
* Updated with the latest Google Analytics code
* No need to find your GA property ID and copy it over, just sign in with Google and choose your site
* See analytics right inside the plugin, the past 7 days vs your previous 7 days
* Shows pageviews, users, pages per session and bounce rate + top 5 traffic referrals
* Wordpress Dashboard widget for 7, 30 or 90 days graph and top site usage stats
* Disable tracking for logged in users like admins or editors for more reliable analytics

= 1.0.7 =
* Added ability to include Google Analytics tracking code on every WordPress page
