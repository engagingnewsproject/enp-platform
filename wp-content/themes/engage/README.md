## ADDING A NEW POST TYPE OR TAXONOMY
1. Add the post type and taxonomy as one file under /Managers/Structures/PostTypes
2. Add the rewrites for the new post type following the format under /Managers/Permalinks
3. Add the rewrites for the vertical under /Managers/Permalinks/addVerticalRewrites()
4. Add the taxonomy slug to the $taxRewriteMap in /Models/Permalinks
5. Register the Post Type to the Vertical Taxonomy under /Managers/Taxonomies/Taxonomies
6. Update Permalinks
7. Register a new filter menu for the item in Globals.php following the format for the other post types
8. Edit /archive.php to specify what filter menu should apply for your new archive, however you need it set-up
9. Go to Options -> Custom Fields -> Archive Landing Pages -> Landing Pages -> Landing Page Type and add the post type slug as an option for this field
10. Test it out!


## Notes on Post Type Archive Queries
Basically the whole site archive structure is powered by queries set in `src/Managers/Permalinks.php`. We've overridden the default queries so we can set our own queries with the verticals added in. There may be a better way to do this, but this way at least gets us a very specific way of modifying the query based on a pretty URL.

To adjust a query, you'll need to add/modify the query in `src/Managers/Permalinks.php` and then re-save the permalinks in Settings->Permalinks.