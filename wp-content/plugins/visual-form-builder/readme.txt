=== Visual Form Builder ===
Contributors: mmuro
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=G87A9UN9CLPH4&lc=US&item_name=Visual%20Form%20Builder&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: form, forms, contact form, contact forms, form, forms, form to email, email form, email, input, validation, jquery, shortcode, form builder, contact form builder, form manager, form creator
Requires at least: 4.7
Tested up to: 5.1.1
Stable tag: 3.0.1
License: GPLv2 or later

Build beautiful, fully functional contact forms in only a few minutes without writing PHP, CSS, or HTML.

== Description ==

*Visual Form Builder* is a plugin that allows you to build and manage all kinds of forms for your website in a single place.  Building a fully functional contact form takes only a few minutes and you don't have to write one bit of PHP, CSS, or HTML!

= Upgrade to VFB Pro =

If you are a fan of Visual Form Builder and want extra features and functionality, [VFB Pro](http://vfbpro.com) is available.

= Features =

* Add fields with one click
* Drag-and-drop reordering
* Simple, yet effective, logic-based anti-SPAM system
* Automatically stores form entries in your WordPress database
* Manage form entries in the WordPress dashboard
* Export entries to a CSV file
* Send form submissions to multiple emails
* jQuery Form Validation
* Customized Confirmation Messages
* Redirect to a WordPress Page or a URL
* Confirmation Email Receipt to User
* Standard Fields
* Required Fields
* Shortcode works on any Post or Page
* Embed Multiple Forms on a Post/Page
* One-click form duplication. Copy a form you've already built to save time
* Use your own CSS (if you want)
* Multiple field layout options. Arrange your fields in two, three, or a mixture of columns.

= Field Types =

* Fieldset
* Section (group fields within a fieldset)
* Text input (single line)
* Textarea (multiple lines)
* Checkbox
* Radio (multiple choice)
* Select dropdown
* Address (street, city, state, zip, country)
* Date (uses jQuery UI Date Picker)
* Email
* URL
* Currency
* Number
* Time (12 or 24 hour format)
* Phone (US and International formats)
* HTML
* File Upload
* Instructions (plain or HTML-formatted text)

= Entries =

* Manage submitted entries in WordPress dashboard
* Bulk Export to CSV
* Bulk Delete
* Advanced Filtering
* Search across all entries
* Collect submitted data as well as date submitted and IP Address
* Disable saving of all entries (GDPR)

= Customized Confirmation Messages =

* Control what is displayed after a user submits a form
* Display HTML-formatted text
* Redirect to a WordPress Page
* Redirect to a custom URL

= Notification Emails =

* Send a customized email to the user after a user submits a form
* Additional HTML-formatted text to be included in the body of the email
* Automatically include a copy of the user's entry

= SPAM Protection =

* Automatically included on every form
* Uses a simple and accessible, yet effective, [text CAPTCHA](http://textcaptcha.com/) verification system

== Installation ==

1. Go to Plugins > Add New
1. Click the Upload link
1. Click Browse and locate the `visual-form-builder.x.x.zip` file
1. Click Install Now
1. After WordPress installs, click on the Activate Plugin link

== Frequently Asked Questions ==

= How do I create a form? =

1. Click on the Visual Form Builder > Add New link and enter a few form details
1. Click the form fields from the box on the left to add it to your form.
1. Edit the information for each form field by clicking on the down arrow.
1. Drag and drop the elements to sort them.
1. Click Save Form to save your changes.

= Can I use my own verification system such as a CAPTCHA? =

reCAPTCHA is available with [Visual Form Builder Pro](http://vfbpro.com).

Visual Form Builder uses a [text CAPTCHA](http://textcaptcha.com/). If you decide to upgrade to Visual Form Builder Pro, you will gain [Akismet](https://akismet.com/) support.

= Emails are not being sent =

*Note*: Form submissions will always be saved in the database whether or not the email was sent.

**Check SPAM folder**

A quick look in the SPAM folder will tell you if the emails are being routed into the folder. If so, simply train your email client to not treat those emails as SPAM

**Configure your site to use SMTP**

Some people have reported that after the form is submitted, no email is received. If this is the case for you, it typically means that your server or web host has not properly configured their SMTP settings.

Try using a plugin such as [WP Mail SMTP](http://wordpress.org/extend/plugins/wp-mail-smtp/) to correct the issue.

**Set the Reply-To email to a same domain email**

Setting up SMTP will get you part of the way there. For most, it solves the problem. For others, it requires additional configuration

If you find that emails are not being sent, you should first confirm that you have completed all of the details in the `Form Settings > Email section`. Next, be sure to set the Reply-To option to an email that exists on the same domain as your WordPress site.

**Set the Sender email to an email that exists on the domain**

In addition to the Reply-To header, some hosts require that the Sender header is also set to an email that exists on the domain.  By default, the Sender email is automatically set to either your admin email if the domain matches.  If it does not match, then a local email address is created (wordpress@yoursitename.com).

To change this behavior to use an email that exists on the domain, you will need to set the Sender Mail Header option on the `Visual Form Builder > Settings` page.

**Possible mod_security conflict**

Some servers are overzealous in their restrictions on the $_POST object and will block anything with certain keywords. Check your server logs and look for any 403 Forbidden or 500 Internal Server errors. If you notice these errors when submitting a form, contact your host and find out if there are any restrictions.

**Enable local mail for your domain**

Be sure to enable local mail delivery for your domain. Disabling local mail delivery is common if you are using an external mail server, but can cause bounce-backs saying the email user does not exist.

Also, if possible, check your server’s email logs or have your host check them for you and see if it’s refusing to send an email. It’s possible your email server is attempting to send the emails but can’t for missing mail resources, security, SPAM filtering, or other technical problems.

= Resolving Theme or Plugin Conflicts =

Visual Form Builder is built using preferred WordPress coding standards. In many cases, some theme authors or plugin developers do not follow these standards and it causes conflicts with those that do follow the standards. The two most common issues have to do with either jQuery or CSS.

**jQuery conflicts**

Visual Form Builder requires at least jQuery version 1.7. Please make sure your theme is updated to use the latest version of jQuery.

**CSS conflicts**

If your forms do not look as expected, chances are there's some CSS in your theme conflicting with the built-in CSS of Visual Form Builder.

**Theme conflicts**

If you have confirmed that you are using the latest version of jQuery and can rule out CSS conflicts, there's probably something in your theme still causing problems.

1. Activate the default "Twenty" theme
1. Test your site to see if the issue still occurs

Still having problems even with the default theme running? If not, it's a conflict with your theme. Otherwise, it's probably a plugin conflict.

**Plugin conflicts**

Before following this process, make sure you have updated all plugins to their latest version (yes, even Visual Form Builder).

1. Deactivate ALL plugins
1. Activate Visual Form Builder
1. Test your site to see if the issue still occurs

If everything works with only Visual Form Builder activated, you have a plugin conflict. Re-activate the plugins one by one until you find the problematic plugin(s).

If, after following the above procedures, you are still having problems please report this issue on the [Support Forum](http://wordpress.org/support/plugin/visual-form-builder).

= Customizing the form design =

By upgrading to VFB Pro, you be able to purchase the [Form Designer](http://vfbpro.com/add-ons/form-designer) add-on which will let you easily customize the design.

= Customizing the Date Picker =

The jQuery UI Date Picker is a complex and highly configurable plugin. By default, Visual Form Builder's date field will use the default options and configuration.

= How do I translate the error messages to my language? =

The validation messages (ex: ‘This field is required’ or ‘Please enter a valid email address’) are generated by the jQuery Form Validation plugin.

By default, these messages are in English. To translate them, you can either use the free add-on Custom Validation Messages or follow the manual JavaScript method.

The recommended method to translate the messages is by installing the free [Custom Validation Messages](http://wordpress.org/extend/plugins/vfb-custom-validation-messages/) add-on.  This will let you easily change the messages for all forms from within the WordPress admin.

If you would rather use the manual method, you will need to follow the instructions below.

Follow these instructions:

In your theme folder, create a JavaScript file. In this example, I'm using `myjs.js`. Add the following code to it and customize the language to what you need:

`jQuery(document).ready(function($) {
    $.extend($.validator.messages, {
        required: "Eingabe nötig",
        email: "Bitte eine gültige E-Mail-Adresse eingeben"
    });
});`

Now, in your functions.php file, add the following piece of code:

`add_action( 'wp_enqueue_scripts', 'my_scripts_method' );
function my_scripts_method() {
   wp_register_script( 'my-vfb-validation',
       get_template_directory_uri() . '/js/my-js.js',
       array( 'jquery', 'jquery-form-validation' ),
       '1.0',
       false );

   wp_enqueue_script( 'my-vfb-validation' );
}`

== Screenshots ==

1. Visual Form Builder page
2. Configuring field item options
3. Entries management screen
4. Rendered form on a page

== Changelog ==

**Version 3.0.1 - Apr 17, 2019**

* Fix bug where a variable was not being declared as an array

**Version 3.0 - Apr 01, 2019**

* Update admin CSS to use SASS for easier future updates

**Version 2.9.9 - Aug 09, 2018**

* Update DB check to prevent extra queries when using Multisite install
* Update uninstall procedure to happen through the VFB > Settings menu instead of the Plugins admin menu

**Version 2.9.8 - Jul 12, 2018**

* Add new Disable Saving Entry feature to Settings to better assist EU users and the General Data Protection Regulation (GDPR)

**Version 2.9.7 - Mar 08, 2018**

* Fix bug with Default Country not able to be selected
* Fix bug with Address label position setting
* Update display class to insure methods are declared as static

**Version 2.9.6 - Feb 12, 2018**

* Fix bug with export

**Version 2.9.5 - Feb 11, 2018**

* Refactor structure of plugin

**Version 2.9.4 - Oct 01, 2017**

* Minor code updates

**Version 2.9.3 - Jul 10, 2017**

* Update list of Countries to add a few new ones

**Version 2.9.2 - Sep 08, 2016**

* Update Date Submitted format to save with leading zeroes to match mySQL format
* Update IP Address column to store longer IPv6 addresses

**Version 2.9.1 - Aug 23, 2016**

* Add Portuguese language translations

**Version 2.9 - Jul 26, 2016**

* Fix regression for recent entries bug fix

**Version 2.8.9 - Jul 24, 2016**

* Fix bug where entries could not be trashed or deleted

**Version 2.8.8 - Apr 28, 2016**

* Fix bug with Export
* Update French translations

**Version 2.8.7 - Mar 30, 2016**

* Fix use of deprecated functions
* Check permissions before adding new forms

**Version 2.8.6 - Sep 21, 2015**

* Update to allow translations to use the WP_LANG_DIR folder for language packs

**Version 2.8.5 - Sep 09, 2015**

* Better secure entry detail page against XSS vulnerability

**Version 2.8.4 - Aug 24, 2015**

* Update how widget is registered to comply with WordPress 4.3

**Version 2.8.3 - May 08, 2015**

* Better secure searching and filtering for forms and entries list

**Version 2.8.2 - Apr 23, 2015**

* Fix bug with WordPress 4.2 and class property variables not being set

**Version 2.8.1 - Apr 12, 2014**

* Add localized jQuery form validation messages for languages that have a VFB translation file
* Add localized jQuery UI datepicker text for languages that have a VFB translation file
* Add vfb_spam_words_exploits, vfb_spam_words_profanity, and vfb_spam_words_misc filters to allow spam sensitivity words to be controlled
* Update the jQuery UI date picker and CKEditor scripts to only load when the respective field is on the form
* Update tooltip to prefix class names to prevent potential conflicts
* Update admin notices to only display on VFB Pro pages
* Update "Add New Form" help text
* Update Export to make sure there are no errors with unserializing before proceeding
* Update Export to more accurately strip all HTML tags for all fields except HTML and Address (where <br> is replaced with new lines)
* Update admin form editor to get fields by reference to improve looping speed
* Update admin with more jQuery UI CSS overrides for the disabled classes
* Update email headers to use array formatting instead of a string
* Update file upload process to check if the $FILE global is an array before proceeding
* Update Display Forms shortcode/template tag to not wrap in <code> tags
* Update CSS to use !important to prevent themes from conflicting with certain rules
* Fix bug with image upload where guid may not be set correctly on some servers
* Fix bug for missing Address description
* Fix bug in export where entries fields did not load the correct page with the right field names
* Fix bug where sanitizing number should sanitize digits, which doesn’t allow decimals

**Version 2.8 - Dec 3, 2013**

* Add Title option to widget
* Add "Unselect All" link to entries export field selection
* Update global form font size
* Update errorClass to more specific vfb-error class name
* Update various admin form filter drop downs to include form ID
* Fix bug where error label border did not display on certain inputs
* Fix bug affecting radio/ checkbox descriptions
* Fix quotes and other encoded characters in export
* Fix PHP notices when global $post is not available
* Minor updates to the admin CSS
* Remove screen_icon which has been deprecated in WordPress 3.8

**Version 2.7.9 - Sep 23, 2013**

* Add "Always load CSS" setting
* Add "Spam word sensitivity" setting
* Add Entry ID to entries list view
* Update number sanitizing to use regular expression instead of floatval
* Update jQuery UI CSS
* Update file input styles
* Update CSS and JS to use cache busting version numbers
* Update email and entry detail to wrap textarea, HTML, and post content in wpautop
* Fix bug where creating a new form did not forward to edit screen immediately
* Fix label "for" attribute output
* Fix bug where lists were unstyled in descriptions
* Fix various “selected” output bugs in admin
* Fix bug where "Show on Screen" would not remember selections
* Fix a couple screen options calls to use action and not filter
* Reduce number of queries on forms list page

**Version 2.7.8 - Aug 13, 2013**

* Add settings page with options for popular filters
* Add vfb_sender_mail_header, vfb_show_version filters
* Add an ID attribute to the form container div
* Update CSS enqueue to only load when form exists on the page
* Update list of user agent strings to test against in vfb_blocked_spam_bots
* Update email from names to use mb_encode_mimeheader for proper encoding
* Update padding on select elements
* Update Instructions field to include CSS Classes and Field Layout options
* Update form output to force bullets to hide, but only if list-style-type isn't set
* Update form output to only display the "for" attribute for certain fields
* Update Export to always download a file, even if no results are found
* Fix bug when sorting and field sequence is not properly set
* Fix bug when uploading an audio or video file in WordPress 3.6
* Deprecate spam check for empty user agent
* Remove texas from spam words

**Version 2.7.7 - Jul 16, 2013**

* Fix bug where confirmation function fails

**Version 2.7.6 - Jul 16, 2013**

* Add sorting to checkbox/radio/select options
* Add dateFormat option for Date fields
* Add admin blue styles
* Add vfb_address_labels_placement, vfb_skip_referrer_check filters
* Update interface icons
* Update form delete process to now delete all collected entries for that form
* Update CSS with more default styles to override potential theme problems
* Update form saving to check for max_input_vars and display error message
* Update saving field name, description, options, and default value to trim whitespace
* Update Legend output to only display bottom border when text is available
* Update submit button name/id attributes to conform to new naming convention
* Fix bug where User Name dropdown would appear when no required text or name fields were set
* Fix form list bulk delete
* Fix sprintf error when max file size has been reached
* Remove unnecessary queries during email
* Remove old “Display Forms” help image and just use text

**Version 2.7.5 - Jun 06, 2013**

* Add Print button to Entry Detail view
* Add Duplicate link to Form List view
* Add meta keyword for plugin version number
* Update HTML field to use CKEditor instead of Quicktags
* Update CSS to include :focus styles
* Update Entry Detail to link File Uploads
* Fix bug during Export for duplicate field names
* Fix bug on Export page where Page to Export option may not appear
* Fix bug on Export page where Fields were not limited to 1000 in an edge case
* Fix bug where delete link did not work in Form List view

**Version 2.7.4 - May 08, 2013**

* Update Numbers field to allow either Digits or Number validation and sanitize as float instead of int
* Update Entries Detail "Delete" link to a "Trash" link
* Fix bug where referer URL did not match domains that prepended www
* Fix bug in form output where file uploads were not being sent
* Fix bug on Entries List to only display approved (i.e. untrashed) entries in All view
* Fix bug on Entries List to properly display Today's Entries
* Minor updates

**Version 2.7.3 - May 07, 2013**

* Fix bug where referer URL was not compatible with certain permalink structures

**Version 2.7.2 - May 05, 2013**

* Add form search in admin
* Add 'Pages to Export' option when more than 1000 entries detected for a single form
* Add Netherlands translation
* Update forms list design
* Update admin to require WordPress 3.5 and jQuery UI 1.9
* Update behavior to allow deselecting Default values on Select/Radio/Checkbox options
* Update some translations
* Update Entries to allow trashing before deleting
* Update style of "Add Form" button above post/page visual editor
* Update and improve sticky sidebar behavior
* Fix bug where nesting and sorting would not save
* Fix bug during Export for certain encoded characters

**Version 2.7.1 - Mar 13, 2013**

* Fix bug in Export where fields did not load in certain cases
* Minor code updates

**Version 2.7 - Feb 28, 2013**

* Add widget for displaying forms in sidebar
* Add dashboard widget for displaying recent entries
* Add DONOTCACHEPAGE constant to fix occasional nonce errors for caching plugin users
* Fix bug where second address line was always required
* Fix bug for misnamed Instructions CSS class
* Fix bug where quotes were not converted on output
* Fix bug where left/right aligned labels and content were not displaying correctly
* Fix bug where export AJAX was not returning properly
* Fix bug for Export Select All fields
* Sanitize IP address before inserting into database
* Rollback Date field type to non-HTML5 to prevent duplicate date pickers in Chrome
* Update language .POT

**Version 2.6.9 - Feb 08, 2013**

* Fix bug where Validation would be removed on saving predefined fields

**Version 2.6.8 - Feb 06, 2013**

* Add Reply-To to email headers for better compatibility with some email servers
* Add new Fields selection in Export
* Update CSV export to be more reliable
* Update certain input field types to HTML5 input types
* Update vfb_address_labels filter to allow control over Address field
* Fix bug where Address field sanitization stripped &lt;br&gt; tags
* Fix bug where i18n file was improperly loaded
* Fix bug in Instructions description where HTML tags were encoded in admin
* Fix bug that allowed validation dropdown to be active in certain predefined fields
* Check DB version and update with proper plugins_loaded action
* Deprecate use of CDN for certain files in favor of locally hosted versions
* Deprecate Export Selected in favor of more reliable exporting on the Export screen

**Version 2.6.7 - Dec 06, 2012**

* Update email headers
* Fix bug where notification email did not send
* Fix textarea value formatting in email

**Version 2.6.6 - Dec 04, 2012**

* Turn off script debugging

**Version 2.6.5 - Dec 04, 2012**

* Add confirmation to Delete field
* Add new Address label filter
* Add new CSV delimiter filter
* Add CSS Class option to Submit button
* Update some queries to be compatible with WordPress 3.5
* Update first fieldset warning and output a more noticeable error
* Update tooltip CSS
* Fix media button to use correct action
* Fix missing un-prefixed classes

**Version 2.6.4 - Nov 12, 2012**

* Fix bug where SVN commit mangled code

**Version 2.6.3 - Nov 12, 2012**

* Update CSS to now prefix all classes to help eliminate theme conflicts
* Update email function to force a From email that exists on the same domain
* Fix bug affecting File Upload field validation
* Fix database install to use PRIMARY KEY instead of UNIQUE KEY
* Fix bug preventing Export from displaying filtering options
* Minor code cleanups

**Version 2.6.2 - Oct 23, 2012**

* Fix bug where File Upload field would prevent validation
* Fix bug when selecting entries export
* Fix bug that hid the entries export options
* Fix bug for another missing Save Form button
* Update JS and CSS from CDN to use HTTPS

**Version 2.6.1 - Oct 17, 2012**

* Fix bug for missing Save Form button
* Fix bug for entries screen options and pagination

**Version 2.6 - Oct 17, 2012**

* Move plugin into its own menu
* Add new 'All Forms' view with an alphabetical group list
* Add new New Form screen
* Add customizable columns to admin form builder (see Screen Options tab)
* Update meta boxes to be reordered or hidden (see Screen Options tab)
* Update and clean up entry form design
* Update email headers to send from admin email for servers having trouble with sending
* Fix bug where form rendering would behave erratically in Internet Explorer 9
* Fix bug where sender emails would be cut off after 25 characters in the entries database

**Version 2.5 - Sep 13, 2012**

* Add new Export page for exporting all entries
* Add IDs to each form item on output
* Fix bug where extra quote was outputting on radio buttons
* Fix bug where form name override was not being updated when copying a form
* Fix bug where address formatting broke in the email
* Deprecate Export All from Entries Bulk Actions (to export, see new Export page)
* Update name attribute to remove field key in attempts to prevent POST limit from reaching max memory
* Update server side validation to check for required fields
* Update server side validation to denote which field is failing
* Minor admin CSS update

**Version 2.4.1 - May 22, 2012**

* Fix bug where misspelled variable caused email to not send

**Version 2.4 - May 22, 2012**

* Fix bug where label alignment option was not being saved
* Update spam bot check to only execute when form is submitted
* Update list of spam bots

**Version 2.3.3 - Apr 30, 2012**

* Fix bug for missing media button image

**Version 2.3.2 - Apr 27, 2012**

* Fix bug that displayed a warning

**Version 2.3.1 - Apr 27, 2012**

* Fix bug where Export feature was broken
* Fix bug where server validation failed on certain data types
* Add months drop down filter to Entries list

**Version 2.3 - Apr 24, 2012**

* Add media button to Posts/Pages to easily embed forms (thanks to Paul Armstrong Designs!)
* Add search feature to Entries
* Add Default Value option to fields
* Add Default Country option to Address block
* Fix bug where Required option was not being set on File Upload fields
* Fix bug where Form Name was not required on Add New page
* Update and optimize Entries query
* Update Security Check messages to be more verbose
* Update email formatting to add line breaks
* Update how the entries files are included to eliminate PHP notices
* Minor updates to CSS

**Version 2.2 - Mar 26, 2012**

* Add Label Alignment option
* Add server side form validation; SPAM hardening
* Add inline Field help tooltip popups
* Add Spanish translation
* Update Form Settings UI
* Update File Upload field to place attachments in Media Library
* Update Field Description to allow HTML tags
* Update Field Name and CSS Classes to enforce a maxlength of 255 characters
* Update jQueryUI version
* Fix bug preventing form deletion

**Version 2.1 - Mar 06, 2012**

* Add Accepts option to File Upload field
* Add Small size to field options
* Add Options Layout to Radio and Checkbox fields
* Add Field Layout to field options
* Add Bulgarian translation
* Update jQuery in admin
* Verification fields now customizable
* Verification field now can be set to not required

**Version 2.0 - Feb 10, 2012**

* Fix bug for misspelled languages folder
* Fix bug for slashes appearing in email and admin
* Fix bug for misaligned rows in CSV export
* Update admin notices functionality
* Update the way Addresses were handled during email
* Add Hungarian translation

**Version 1.9.2 - Jan 09, 2012**

* Bug fix for copied forms with nested fields

**Version 1.9.1 - Jan 04, 2012**

* Bug fix for Sender Name, Email, and Notification Email overrides

**Version 1.9 - Jan 03, 2012**

* Add ability for fields to be nested underneath Fieldsets and Sections
* Add Section Form Item
* Update adding/deleting fields to use AJAX
* Update and improve admin tabs functionality
* Update new form building to no longer force require email details
* Update Delete Form link to require confirmation before deleting

**Version 1.8 - Nov 22, 2011**

* Add Dynamic Add/Delete for Options for Radio, Select, and Checkbox fields
* Add Dynamic Add/Delete for Email(s) To field
* Add CSS Classes configuration option
* Update Instructions field to allow for images
* Submit button text value now customizable

**Version 1.7 - Nov 09, 2011**

* Add Instructions Form Item
* Add Duplicate Form feature
* Add Sender Name and Sender Email customization fields to Notifications
* Update CSS

**Version 1.6 - Oct 07, 2011**

* Fix bug where multiple address blocks could not be used
* Add internationalization support
* Add auto-respond feature to separately notify your users after form submission
* Update jQuery Validation to 1.8.1

**Version 1.5.1 - Sep 08, 2011**

* Fix bug where missing jQuery prevented multiple form fix from working

**Version 1.5 - Sep 07, 2011**

* Fix bug where multiple forms on same page could not be submitted individually
* Fix bug where Entries form filter did not work
* Update admin CSS to use it's own file instead of one loaded form WordPress

**Version 1.4 - Aug 16, 2011**

* Fix bug where database charset wasn't being set and causing character encoding issues
* Fix date submitted to match local date and time settings
* Fix Textarea CSS to respond to large size
* Add File Upload and HTML Form Items
* Add Entries Export feature
* Update View Entries to full page view instead of jQuery show/hide quick view

**Version 1.3.1 - Jul 28, 2011**

* Fix bug where new Confirmation screen was not being installed
* Fix bug where escaped names and descriptions were not being stripped of slashes properly
* Add missing sprite image for Form Items

**Version 1.3 - Jul 27, 2011**

* Fix bug where jQuery validation was missing from security field
* Update Form Items UI to make it easier and quicker to add fields
* Add six more Form Items
* Add Confirmation customization
* Update CSS output for some elements

**Version 1.2.1 - Jul 19, 2011**

* Fix bug where entries table does not install

**Version 1.2 - Jul 19, 2011**

* Fix bug where reserved words may have been used
* Fix bug where multiple open validation dropdowns could not be used in the builder
* Add entries tracking and management feature
* Improve form submission by removing wp_redirect
* Add Sender Name and Email override

**Version 1.1 - Jun 30, 2011**

* Fix bug that prevented all selected checkbox options from being submitted
* Add more help text on contextual Help tab
* Fix missing closing paragraph tag on success message

**Version 1.0 - Jun 23, 2011**

* Plugin launch!

== Upgrade Notice ==

= 2.9 =
Fix regression for recent entries bug fix

= 2.8.9 =
Fix bug where entries could not be trashed or deleted

= 2.8.8 =
Fix critical bug with Export

= 2.8.7 =
This version requires at least WordPress 4.3 or higher due to previous use of deprecated functions

= 2.8.6 =
Update to allow translations to use the WP_LANG_DIR folder for language packs

= 2.8.5 =
Better secure entry detail page against XSS vulnerability

= 2.8.4 =
Update how widget is registered to comply with WordPress 4.3

= 2.8.3 =
Better secure searching and filtering for forms and entries list

= 2.8.2 =
Fix bug with WordPress 4.2 and class property variables not being set

= 2.8 =
Fix quotes and other encoded characters in export

= 2.7.9 =
Add "Always load CSS" setting, various updates and bug fixes

= 2.7.8 =
Add settings page. Update CSS enqueue to only load when form exists on the page

= 2.7.7 =
Fix bug where confirmation function fails

= 2.7.6 =
Add sorting to checkbox/radio/select options. Update interface icons. Bug fixes.

= 2.7.5 =
Update HTML field to use CKEditor. Fix Export bugs.

= 2.7.4 =
Fix bug where referer URL did not match domains that prepended www. Fix bug where file uploads were not being sent. Other updates and fixes.

= 2.7.3 =
Fix bug where referer URL was not compatible with certain permalink structures

= 2.7.2 =
Add 'Pages to Export' option. Update plugin to require WordPress 3.5 and jQuery UI 1.9

= 2.7.1 =
Fix bug in Export where fields did not load in certain cases

= 2.7 =
Add sidebar and dashboard widgets. Fix Export bugs.

= 2.6.9 =
Fix bug where Validation would be removed on saving predefined fields

= 2.6.8 =
Add Reply-To to email headers for better compatibility with some email servers; updated CSV export

= 2.6.7 =
Fix bug where notification email did not send

= 2.6.5 =
Update some queries to be compatible with WordPress 3.5

= 2.6.2 =
Fix bug where File Upload field would prevent validation, JS and CSS now work on HTTPS, minor fixes.

= 2.6 =
VFB now in its own menu, new All Forms UI, other bug fixes

= 2.5 =
Improved Export entries page, improved server side validation

= 2.4.1 =
Update spam bot check, fixed bug where label alignment option was not being saved

= 2.4 =
Update spam bot check, fixed bug where label alignment option was not being saved

= 2.3.3 =
Fixed missing media button image

= 2.3.2 =
Fixed export entries feature and added a date filter to the entries list

= 2.3.1 =
Fixed export entries feature and added a date filter to the entries list

= 2.3 =
Added media button, Entries search and default values

= 2.2 =
Updated Form Settings UI. Additional SPAM hardening, new inline help tooltips, file uploads now added to Media Library, and a lot more!

= 2.1 =
Please note this version requires WordPress 3.3.  Please update your WordPress install before upgrading to Visual Form Builder 2.1.

= 2.0 =
Bug fix misaligned rows in CSV export, misspelled languages folder, and slashes appearing in emails and admin. Other minor improvements.

= 1.9.2 =
Bug fix for copied form with nested fields.

= 1.9.1 =
Recommend update! Bug fix for Sender Name, Email, and Notification Email overrides.

= 1.9 =
Added Section Form Item, ability to nest fields under Fieldsets and Sections. Improve adding/deleting fields.

= 1.8 =
Submit button text now customizable (click Save Form to access). Added dynamic add/delete for Radio, Select, Checkboxes, and Email(s) To fields.

= 1.7 =
Added Instructions Form Item, Duplicate Form feature, and more customizations to the Notifications.

= 1.6 =
Added auto-responder feature, internationalization support, and fixed validation problems for IE users.

= 1.5.1 =
Fix bug where missing jQuery prevented multiple form fix from working.

= 1.5 =
Fix for submitting multiple forms on a single page. Other bug fixes and improvements.

= 1.4 =
Export entries to a CSV, file uploads, and various bug fixes.

= 1.3.1 =
Recommended update immediately! Fix for bug where confirmation screen does not install.

= 1.3 =
New, faster way to add form items and ability to customize Confirmation. Fix for validation on security field.

= 1.2.1 =
Recommended update immediately! Fix for bug where entries table does not install.
