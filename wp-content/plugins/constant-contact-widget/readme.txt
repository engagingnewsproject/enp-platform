=== Plugin Name ===
Contributors: sourcefound
Donate link: http://memberfind.me
Tags: constant contact
Requires at least: 3.0.1
Tested up to: 4.2.2
Stable tag: 1.9.2
License: GPL2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Constant Contact plugin that adds a really lightweight, clean and simple widget that collects email addresses to a Constant Contact list.

== Description ==

Constant Contact Widget is a really lightweight, clean and simple widget that collects email addresses to a contact list in your Constant Contact account.

* PageSpeed optimized with minimized HTML and inlined javascript (entire widget adds about 1200 bytes).
* Ajax form submission - no page refresh and minimizes spam.
* Cross-browser compliant javascript with no dependencies (no jQuery required).
* Lightweight code (~120 lines of PHP code) that does not slow down your site.
* No CSS so you can style it to match your theme.
* Clean and simple, with no spurious text, images, advertising or links.
* Does not send any information to our servers or any other third party servers, it only interfaces with Constant Contact.

Constant Contact Widget lets you setup:

* Title for the Constant Contact widget
* Description
* Text for the submit button
* Constant Contact list to add the email address to
* Option to request first and last name
* Display a thank you message upon successful submission to Constant Contact

You can also setup the Constant Contact Widget to redirect to a URL upon successful submission of information to Constant Contact.

You can load multiple Constant Contact Widgets each with distinct settings - for example, you can specify a different Constant Contact list for each Constant Contact widget.

New for 1.8! Constant Contact Widget can now be loaded on a page using a shortcode. Multiple Constant Contact Widgets can be loaded, using shortcodes or widgets or combinations of both.

New for 1.9! Constant Contact Widget now has the option to require a user to check a checkbox for consent to be added to the Constant Contact mailing list.

Note: Constant Contact account required.

== Installation ==

1. Constant Contact Widget plugin can be downloaded from the WordPress.org plugin directory or uploaded directly.
1. Activate the Constant Contact Widget plugin.
1. Go to Settings > Constant Contact and enter your Constant Contact login and password.
1. Go to Appearance > Widgets and add the Constant Contact widget to the appropriate sidebar.
1. Under the Constant Contact widget settings, you can set the title, description, button text and the name of the Constant Contact list you want the email address to be added to.
1. You can specify either a success message or an URL. Upon successful submission of the address to Constant Contact, if a message is used, that message will replace the form in the Constant Contact widget box. If a URL is specified, the user will be redirected to that URL.
1. Option you can also request the first and last name fields on the form.
1. Option you can also require that the user checks a checkbox for consent to be added to your Constant Contact mailing list.

== Frequently Asked Questions ==

= Can I use this plugin without a Constant Contact account? =

This widget is designed to add a email address to your Constant Contact list, so you will need a Constant Contact account.

= Using the Constant Contact Widget shortcode =

Use the following shortcode:

\[constantcontactwidget grp="constant contact list name" btn="button text" msg="success message"\]

If you wish to also ask for the first and last name, add nam="1" like this:

\[constantcontactwidget nam="1" grp="constant contact list name" btn="button text" msg="success message"\]

If you wish to require that the user checks a checkbox for consent to be added to your Constant Contact mailing list, add req="your consent message", for example:

\[constantcontactwidget nam="1" grp="constant contact list name" btn="button text" msg="success message" req="I agree to be added to the mailing list"\]

If you like to redirect the visitor to another page after a successful submission with the Constant Contact Widget, simply place the url in the msg attribute instead of a message.

= How do I style the Constant Contact widget? =

The Constant Contact widget is contained within a form element with the class "constantcontactwidget\_form". 

Using your favourite browser developer tool, see what styles your theme is applying to the widget, and add the class ".widget\_sf\_widget_constantcontact" to increase the priority of your style to target the Constant Contact widget.

= Does the Constant Contact Widget collect and monitor stats? =

Nope. Constant Contact Widget is designed to do a simple thing well, and do it efficiently.

= How does the Constant Contact widget help combat spam? =

It uses javascript to submit the form via Ajax, the HTML form element doesn't expose an action or method. This makes it harder for a automated crawler to post spam via the form, as most crawlers do not run javascript.

== Changelog ==

= 1.0 =
* First release of Constant Contact Widget

= 1.1 =
* Improved error handling and feedback in Constant Contact Widget

= 1.2 =
* Replaced deprecated WordPress function attribute\_escape

= 1.3 =
* Added option to collect names to Constant Contact Widget

= 1.4 =
* Javascript rewritten to be wptexturize friendly when Constant Contact is embedded using the\_widget()

= 1.5 =
* Constant Contact Widget now allows redirection to url on success

= 1.6 =
* Wrapped Constant Contact widget in form element, allows enter key to submit form

= 1.7 =
* Javascript is now tolerant of extra html elements in the Constant Contact Widget

= 1.8 =
* Adds shortcode feature
* Fixed issues with encoding of Constant Contact list names

= 1.8.1 =
* Adds classname to form
* Allows html in success message

= 1.8.2 =
* Prevents warnings from ob_clear

= 1.9 =
* Adds optional consent required checkbox 

= 1.9.1 =
* Uses SSL endpoint for Constant Contact API

= 1.9.2 =
* Fixes issue with saving credentials when previous credentials contain special characters