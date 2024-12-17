# CME Engage 2.x Theme

[![Build Status](https://travis-ci.com/timber/starter-theme.svg?branch=master)](https://travis-ci.com/github/timber/starter-theme)
[![Packagist Version](https://img.shields.io/packagist/v/upstatement/timber-starter-theme?include_prereleases)](https://packagist.org/packages/upstatement/timber-starter-theme)

Engage 2.x is forked from the [Timber Starter Theme](https://github.com/timber/starter-theme): "_s" for Timber: a dead-simple theme that you can build from. The primary purpose of this theme is to provide a file structure rather than a framework for markup or styles. Configure your SASS files, scripts, and task runners however you would like!

## Installing the Engage 2.x theme

1. After you have cloned this repo from the command line `cd` into your site `/themes/engage-2-x/` directory and run 
  ```
  composer require timber/timber
  ```
  you should see `Using version ^2.0 for timber/timber` in the terminal output.
  
2. Activate the `engage-2-x` theme from WP Admin / Appearance.

3. Now you can run `yarn watch` to start up your local dev server with browsersync for automatic refresh.

4. When your tasks are complete and you are ready to push your changes to the remote repo run `yarn prod` on the /engage-2-x directory to compile all CSS & JS.


More help on [Timber Installation Instructions](https://timber.github.io/docs/v2/installation/installation/#install-timber-into-an-existing-project).

Then,

1. Rename the theme folder to something that makes sense for your website. You could keep the name `timber-starter-theme` but the point of a starter theme is to make it your own!
2. Activate the theme in the WordPress Dashboard under **Appearance → Themes**.
3. Do your thing! And read [the docs](https://timber.github.io/docs/).

## The `StarterSite` class

In **functions.php**, we call `new StarterSite();`. The `StarterSite` class sits in the **src** folder. You can update this class to add functionality to your theme. This approach is just one example for how you could do it.

The **src** folder would be the right place to put your classes that [extend Timber’s functionality](https://timber.github.io/docs/v2/guides/extending-timber/).

Small tip: You can make use of Composer’s [autoloading functionality](https://getcomposer.org/doc/04-schema.md#psr-4) to automatically load your PHP classes when they are requested instead of requiring one by one in **functions.php**.

## What else is there?

- `static/` is where you can keep your static front-end scripts, styles, or images. In other words, your Sass files, JS files, fonts, and SVGs would live here.
- `views/` contains all of your Twig templates. These pretty much correspond 1 to 1 with the PHP files that respond to the WordPress template hierarchy. At the end of each PHP template, you’ll notice a `Timber::render()` function whose first parameter is the Twig file where that data (or `$context`) will be used. Just an FYI.
- `tests/` ... basically don’t worry about (or remove) this unless you know what it is and want to.

## Other Resources

* [This branch](https://github.com/laras126/timber-starter-theme/tree/tackle-box) of the starter theme has some more example code with ACF and a slightly different set up.
* [Twig for Timber Cheatsheet](http://notlaura.com/the-twig-for-timber-cheatsheet/)
* [Timber and Twig Reignited My Love for WordPress](https://css-tricks.com/timber-and-twig-reignited-my-love-for-wordpress/) on CSS-Tricks
* [A real live Timber theme](https://github.com/laras126/yuling-theme).
* [Timber Video Tutorials](http://timber.github.io/timber/#video-tutorials) and [an incomplete set of screencasts](https://www.youtube.com/playlist?list=PLuIlodXmVQ6pkqWyR6mtQ5gQZ6BrnuFx-) for building a Timber theme from scratch.

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

### **IMPORTANT** 
#### _YOU MUST NOTIFY KAT BEFORE YOU PUSH ANY UPDATES TO THE LIVE SITE_
#### AGAIN: _YOU MUST NOTIFY KAT BEFORE YOU PUSH ANY UPDATES TO THE LIVE SITE_

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
    $ git push prod stable               // push stable to production
    ```

5. Login to WP Engine and clear all caches

![WP Engine Clear all caches](https://i.ibb.co/dQY3vR6/Screen-Shot-2021-06-22-at-9-33-49-AM.png)

6. Clear [Cloudflare Cache](https://dash.cloudflare.com/85a3e11c229eb4e8e12176355e3136e6/mediaengagement.org/caching/configuration)

## Deployment quick instructions

1. #### YOU MUST NOTIFY KAT BEFORE YOU PUSH ANY UPDATES TO THE LIVE SITE

2. First run `yarn prod` to compile files for production.

3. Push to the dev site (https://cmedev.wpengine.com/)
    
```
git push dev master
```
    
4. Merge `master` into `stable`
    
```
git checkout stable && git merge master
```

5. Tag and push to staging site (https://cmestaging.wpengine.com/)

```
git tag -a 2.2.8 -m "message here" && git push origin stable --tags && git push staging stable
```

6. #### _!!REMINDER:_ YOU MUST NOTIFY KAT BEFORE YOU PUSH ANY UPDATES TO THE LIVE SITE

7. Push to the live site

```
git push prod stable
```

## Updated setup instructions

After pulling from the live production site is complete, in the Local App click "open site" button.
- if a browser tab opens with "unknown quiz config path for localhost"
  - open `wp-content/enp-quiz-config.php` and add below `else if` statement at line 7
    -   ```
        else if($site === 'localhost:10038') {
            $path = 'mediaengagementorg';
        }
        ```
        `localhost:10038` is on the local app as "Site Host"
        `mediaengagementorg` is the directory name where your site lives

# SSH Help

ssh into production site

`ssh -i ~/.ssh/wpengine_rsa -o IdentitiesOnly=yes cmengage@cmengage.ssh.wpengine.net`

Get directory sizes:

`$ du -h --max-depth 1` 

or to sort

`$ du -h --max-depth 1|sort -h`

## GitHub History

[Oldest commits page](https://github.com/engagingnewsproject/enp-platform/commits/master?after=78bbeb19687639d0cdde4f988e90ac68699e118b+2484&branch=master&qualified_name=refs%2Fheads%2Fmaster)