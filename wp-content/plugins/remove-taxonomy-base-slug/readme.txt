=== Remove Taxonomy Base Slug ===
Contributors: alexvorn2
Tags: rewrite, slug, taxonomy, category, tag, term
Requires at least: 3.4
Tested up to: 3.9
Stable tag: 2.1

This plugin can remove specific taxonomy base slug from your permalinks (e.g. "/genre/fiction/" to "/fiction/").

== Description ==

If you would like to have a little more nice permalinks to your custom post types articles with custom permalinks - this plugin can help you with this.
If the term has the same slug as the post type, then the taxonomy has the priority over the post type. 

Now I will show some examples:

(Custom taxonomy:) 

= From: =
site.com/taxonomy/cars

= To: =
site.com/cars

(Custom taxonomy with child taxonomies)

= From: =
site.com/taxonomy/cars/bmw

= To: =
site.com/cars/bmw

(Term slug is the same as the post type slug:) 

= From: =
(Post Type:) site.com/cars/
(Term from Taxonomy:) site.com/taxonomy/cars/
(Subterm from Taxonomy:) site.com/taxonomy/cars/bmw

= To: =
(Post Type:) site.com/cars/ (will not show)
(Term from Taxonomy:) site.com/cars (will show)
(Subterm from Taxonomy:) site.com/cars/bmw (will show)


== Installation ==

1. Unzip
2. Upload "remove-taxonomy-base-slug" folder to the "/wp-content/plugins/" directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Select which taxonomies base slug to remove, go to Plugins -> Remove Taxonomy Base Slug
5. Update your permalinks, go to Settings -> Permalinks -> Save Changes
6. That's it!


== Frequently Asked Questions ==

= Why should I use this plugin? =

Use this plugin if you want to get rid of a custom taxonomy base slug completely. 
The normal behaviour of WordPress is to add '/taxonomy' to your permalinks if you leave "taxonomy page" blank (ex: site.com/taxonomy/). 
So your taxonomy links look like "site.com/taxonomy/my-taxonomy/". 
With this plugin your taxonomy links will look like "site.com/my-taxonomy/" (or "site.com/my-taxonomy/sub-taxonomy/" in case of child taxonomies).

= Will it break any other plugins? =

I don't think so.

= Won't this conflict with pages or post types? =

Simply don't have a page or a post type and category with the same slug. 
Even if they do have the same slug it won't break anything.
Priority will be for taxonomies.

= Can you add a feature X? =

Depends, if its useful enough and I have time for it.


== Changelog ==

= 2.1 = 

* Fixed a little bug.
* Added Multisite support.

= 2.0 = 

* Fixed: A bug in PHP 5.4 + some other bugs
* Added: Admin Panel with the list of all taxonomies.

= 1.2 =

* Fixed: Auto update permalinks after adding new terms.

= 1.1 =

* Fixed a bug with hierarchical terms.

= 1.0.1 =

* Some small bug fixes.

= 1.0 =

* First release.
