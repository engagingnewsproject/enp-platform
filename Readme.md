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

1. The `.nvmrc` ([/wp-content/themes/engage/.nvmrc](https://github.com/engagingnewsproject/enp-platform/blob/master/wp-content/themes/engage/.nvmrc)) file contains the Node version required for the project. In order to enable the version switch on each dev session you need to first run:

       nvm use

    . . . this command will switch your project node version to the version in the `.nvmrc` file. For windows users, checkout [nvm for windows](https://github.com/coreybutler/nvm-windows). Then you can run the commands below:

2. Install packages by running

       npm install

3. To open a browser window with live reloading run:

       npm run watch

4. When you are done, to compile your code & minify for the production server be sure to run:

       npm run production