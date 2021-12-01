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

# Deployment Summary

Before doing any deployment, **make sure the .js and .css files are minified**.

There are three sites that make up our deployment flow:

1. Dev:         [https://cmedev.wpengine.com](https://cmedev.wpengine.com)
2. Staging:     [https://cmestaging.wpengine.com](https://cmestaging.wpengine.com)
3. Production:  [https://mediaengagement.org](https://mediaengagement.org)

If it is not [a hotfix](#hotfix-branches), the flow for a normal deployment is:



1. `master` gets deployed to [https://cmedev.wpengine.com](https://cmedev.wpengine.com) for testing.

    ```
    $ git push dev master                    // deploy to dev
    ```


2. If all checks out well, `master` gets merged into `stable`:

    ```
    $ git checkout stable                    // change to the stable branch
    $ git merge master                       // merge without the commit object so we can just tag the spot instead of having a separate commit. Kind of like an active, rolling release branch
    ```
    > In terminal VIM enter `:wq` to write the current file and exit.
    ```
    $ git tag -a <tag> -m "<and message if you want or just the tag>"    // tags the fix
    $ git push origin stable --tags           // push tag changes

    ```

3. Push stable to staging for testing:

    ```
    $ git push staging stable                 // push stable to staging
    ```

4. If all checks out well, push stable to production for the launch:

    ```
    $ git push production stable               // push stable to production
    ```

5. Login to WP Engine and clear all caches

![WP Engine Clear all caches](https://i.ibb.co/dQY3vR6/Screen-Shot-2021-06-22-at-9-33-49-AM.png)
