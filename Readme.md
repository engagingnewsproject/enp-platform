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

**Updated instructions**: Engage v1.2.0 includes Timber v1.0 which is installed via composer as the Timber plugin is no longer supported. Not required, but [view Timber Composer installation instructions here](https://timber.github.io/docs/getting-started/switch-to-composer/).

1. After cloning this repo, run these commands from the Engage theme directory: `[local app site directory]/app/public/wp-content/themes/engage`

2. The `.nvmrc` ([/wp-content/themes/engage/.nvmrc](https://github.com/engagingnewsproject/enp-platform/blob/master/wp-content/themes/engage/.nvmrc)) file contains the Node version required for the project. In order to enable the version switch on each dev session you need to first run:

       nvm use

    . . . this command will switch your project node version to the version in the `.nvmrc` file. For windows users, checkout [nvm for windows](https://github.com/coreybutler/nvm-windows). Then you can run the commands below:

3. Install packages by running

       npm install

4. To open a browser window with live reloading run:

       npm run watch

5. **IMPORTANT** When you are done, to compile your code & minify for the production server be sure to run:

       npm run production
       
## Debug Local App Connection

If you run into any issues with the Engage theme try some of these workarounds to get the site and wp-admin showing up.

- __Switch to WordPress default theme.__

  1. If you cannot access wp-admin rename the theme in your project directory to something like `/engage-0`. This will make WordPress switch to the default theme.
	
  2. Disable all plugins except for Advanced Custom Fields (Engage theme depends on this plugin.)
	
  3. Reactivate the engage theme by renaming it back to `/engage` in the project directory.
  
     _This is an important step, as you will need WordPress to recognize the Engage theme so you can reactivate it on the WordPress dashboard at the Appearance/Themes page._
	
- __Deactivate the Engaging Quiz plugin__

  - This plugin is notorious for causing issues, so keep it deactivated to mitigate any related issues.