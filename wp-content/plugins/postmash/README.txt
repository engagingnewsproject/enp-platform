=== postMash - custom post order ===
Contributors: JoelStarnes
Tags: order posts, ajax, re-order, drag-and-drop, admin, manage, post, posts
Requires at least: 2.1
Tested up to: 2.8.4
Stable tag: 1.2.0

Customise the order your posts are display in using this simple drag-and-drop Ajax interface.

== Description ==

Posts are usually listed in reverse chronological order as they are often used for posting regular time-orientated content.
postMash lets you customise the order your posts are listed in using it's simple Ajax drag-and-drop administrative interface. Plus it gives quick access to toggle posts between draft and published states. Particularly useful if you're using WordPress as a CMS.
It now no longer requires any modifications to your template code. If you disable postMash your post will go back to your usual ordering.

Feedback: http://joelstarnes.co.uk/contact

== Installation ==

You no longer need to modify your template code in any way!!
-------------------------------------------------------------------


Here is the old install info, not needed now, but maybe useful if you want to remove the old code from you template, but can't remember what you changed..

Open wp-content/themes/your-theme-name/index.php and find the beginning of ‘the loop’. Which will start: `if(have_posts())`. Then add the following code directly before this:
`
<?php  
    $wp_query->set('orderby', 'menu_order');  
    $wp_query->set('order', 'ASC');  
    $wp_query->get_posts();  
?>
`


This just tells WP to get the posts ordered according to their ‘menu_order’ position. Therefore you can get the posts ordered anytime you use a function such as get_posts simply by giving it the required arguments:
`
<?php get_posts('orderby=menu_order&order=ASC'); ?>
`

Checkout the get_posts() function in the wordpress codex for more info.
Note that it says menu_order is only useful for pages, posts have a menu_order position too, it just isn’t used. postMash provides you with an iterface so that you can use it.


== Frequently Asked Questions ==

If you have any questions or comments, please drop me an email: http://joelstarnes.co.uk/contact/

= None of it is working =
The most likely cause is that you have another plugin which has included an incompatible javascript library onto the postMash admin page. If this is the case all the posts will be shaded grey.

Try opening up your WP admin and browse to your postMash page, then take a look at the page source. Check if the prototype or scriptaculous scripts are included in the header. If so then the next step is to track down the offending plugin, which you can do by disabling each of your plugins in turn and checking when the scripts are no longer included.

= Do I need any special code in my template =
Nope, not any more.

= Which browsers are supported =
Any good up-to-date browser should work fine. I test in Firefox, IE7, Safari and Opera.

==Change Log==
= 1.2.0 =
 - No longer requires modifcation to your template
 - Correctly locates plugin directory, in case you've relocated it

= 1.1.0 = 
 - Updated menu position for WP2.7
 - Better install instructions
 
= 1.0.1 =
 - Fixed incorrect case in the path name to saveList.php
 
= 1.0. =
 - Initial Release

== Localization ==

Currently only available in english.