<!----- Conversion time: 1.029 seconds.


Using this Markdown file:

1. Cut and paste this output into your source file.
2. See the notes and action items below regarding this conversion run.
3. Check the rendered output (headings, lists, code blocks, tables) for proper
   formatting and use a linkchecker before you publish this page.

Conversion notes:

* Docs to Markdown version 1.0β21
* Thu Apr 02 2020 11:55:39 GMT-0700 (PDT)
* Source doc: CME Updated Readme.md
----->

Engage is a [Timber](https://timber.github.io/docs/)(Twig) powered WordPress theme for [The Center for Media Engagement](https://mediaengagement.org/) at University of Texas at Austin.

_** Currently this repo includes the whole WordPress installation. This is not reccommended, but helps sync dev, staging & production enviroments. The only directory you should concern yourself with is in the actual `wp-content/themes/engage` directory. All other files are for the live sites and should not be changed._

# Installation

## Installation - Summary
- Download [WP Engine Local App](http://localwp.com/).
- Connect with CME WP Engine account.
- Download mediaengagement.org from the Local App
- Connect to the GitHub repo & fetch.
- Install npm
- Develop

## Installation Full Instructions:

[View the long installation instructions here.](https://github.com/engagingnewsproject/enp-platform/wiki/Development#installation)

# Local development

**Updated instructions**: Engage 2.x is forked from the [Timber Starter Theme](https://github.com/timber/starter-theme). Not required, but [view Timber Composer installation instructions here](https://timber.github.io/docs/getting-started/switch-to-composer/).

1. After cloning this repo, run these commands from the Engage theme directory: `[local app site directory]/app/public/wp-content/themes/engage`

2. Make sure you are on the latest version of `node` by running `npm doctor`. If anything comes up as not `ok` update your version of `npm` or `node`.

3. Install packages by running

       npm install
       
4. Install Timber with composer and run 
  ```
  composer require timber/timber
  ```
  you should see `Using version ^2.0 for timber/timber` in the terminal output.
  
5. Activate the `engage-2-x` theme (if not active) from WP Admin / Appearance.

6. Now you can run `npm run watch` to start up your local dev server with browsersync for automatic refresh.

7. When your tasks are complete and you are ready to push your changes to the remote repo run `npm run production` on the /engage-2-x directory to compile all CSS & JS.

## Deployment

Project lead only instructions:

1. #### YOU MUST NOTIFY KAT BEFORE YOU PUSH ANY UPDATES TO THE LIVE SITE

2. First run `npm run production` to compile files for production.

3. Push to the dev site.
    
```
git push dev master
```
    
4. Merge `master` into `stable`
    
```
git checkout stable && git merge master
```

5. Tag and push to staging site.

```
git tag -a 2.2.8 -m "message here" && git push origin stable --tags && git push staging stable
```

6. #### _!!REMINDER:_ YOU MUST NOTIFY KAT BEFORE YOU PUSH ANY UPDATES TO THE LIVE SITE

7. Push to the live site

```
git push production stable
```

## Debug Local App Connection

If you run into any issues with the Engage theme try some of these workarounds to get the site and wp-admin showing up.

- Check the Local App setup:

  - If your Local App Web server is set to Apache switch the server to ngnix (this usually fixes the problem)
  - PHP version above 8.1.0, something like 8.2.10.

- __Switch to WordPress default theme.__

  1. If you cannot access wp-admin rename the theme in your project directory to something like `/engage-0`. This will make WordPress switch to the default theme.
	
  2. Disable all plugins except for Advanced Custom Fields (Engage theme depends on this plugin.)
	
  3. Reactivate the engage theme by renaming it back to `/engage` in the project directory.
  
     _This is an important step, as you will need WordPress to recognize the Engage theme so you can reactivate it on the WordPress dashboard at the Appearance/Themes page._

- You might have to run `composer require hellonico/timber-dump-extension` to get the [Timber Dump Extension](https://github.com/nlemoine/timber-dump-extension#timber-dump-extension).
	
- __Deactivate the Engaging Quiz plugin__

  - This plugin is notorious for causing issues, so keep it deactivated to mitigate any related issues.
