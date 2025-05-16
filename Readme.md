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

# Engage Theme for CME

Engage is a [Timber](https://timber.github.io/docs/)-powered WordPress theme for [The Center for Media Engagement](https://mediaengagement.org/) at the University of Texas at Austin.

> **Note:** This repo includes the entire WordPress installation. However, the only directory you should work on is `wp-content/themes/engage-2-x`. Avoid making changes to other files as they are tied to live sites.

## Installation

### Summary

1. Install [WP Engine Local App](http://localwp.com/).
2. Connect with the CME WP Engine account.
3. Download `mediaengagement.org` from Local App.
4. Clone this GitHub repo and fetch.
5. Install npm dependencies.
6. Start development.

For full installation instructions, refer to the [development guide](https://docs.mediaengagement.org/mediaengagement/#installation).

## Local Development

Engage 2.x is forked from the [Timber Starter Theme](https://github.com/timber/starter-theme). You can follow the [Timber Composer installation guide](https://timber.github.io/docs/getting-started/switch-to-composer/) for additional setup details.

### Steps:

1. Clone the repo and navigate to the theme directory:

    ```bash
    cd yourSiteName/app/public/wp-content/themes/engage-2-x
    ```

2. Check if you're using the correct Node version:

    ```bash
    npm doctor
    ```

3. Install npm dependencies:

    ```bash
    npm install
    ```

4. Install Timber via Composer:

    ```bash
    composer require timber/timber
    ```

5. Activate the `engage-2-x` theme from WP Admin → Appearance.

6. Run the development server:

    ```bash
    npm run watch
    ```
    or
    ```bash
    yarn watch
    ```

7. Before pushing changes, compile assets for production:

    ```bash
    yarn prod
    ```

## Debugging Local App Connection

If you encounter issues with the Engage theme:

1. **Check the Local App setup:**
    - Switch the web server to nginx.
    - Ensure PHP version is 8.2.10 or higher.

2. **Switch to a default theme:**
    - Rename the `engage` theme folder to force WordPress to switch to a default theme.
    - Disable all plugins except for ACF.
    - Rename the theme back and reactivate it in WP Admin.

3. **Install Timber Dump Extension (if needed):**

    ```bash
    composer require hellonico/timber-dump-extension
    ```

4. **Deactivate the Engaging Quiz Plugin:** It is known to cause issues.

## Deployment (for project leads only)

Github Actions deploy to each site via `public/.github/workflows/*.yml` files. See [WP Engine Branched Deploys to WP Engine with GitHub Actions](https://wpengine.com/builders/branched-deploys-wp-engine-github-actions/). _Pushing changes to the `master` branch does not deploy to the dev site._

1. **Notify Kat** before pushing updates to the live site.
2. Compile production assets:

    ```bash
    yarn prod
    ```

3. Merge and push changes to the development site:

    ```bash
    git checkout dev && git merge master && git push
    ```

4. Merge and push changes to the staging site:

    ```bash
    git checkout stage && git merge master && git push
    ```

5. **Notify Kat** before pushing to the live site.
6. Merge changes to the production site:

    ```bash
    git checkout stable && git merge master
    ```

    `:wq` + enter
    
7. Push to the production site:

    ```bash
    git push
    ```

### Sync Production Database → Development

> **Only for your Dev environment.** This will not touch uploads or code—just the DB.

1. Make sure you’ve set your SSH keys as described in `ssh/sync-db.sh`.  
2. Confirm you have the Yarn script in your `package.json`:
    ```jsonc
    {
      "scripts": {
        "sync-db": "bash ssh/sync-db.sh"
      }
    }
    ```
3. From the theme root, run:

    #### Dev Site
    ```bash
    yarn sync-db-dev
    ```
    This will:
    - Export the Production database  
    - Stream it into Development  
    - Search-replace your live URL with the dev URL  
    - Flush the WP cache  

    #### Staging Site
    ```bash
    yarn sync-db-staging
    ```
    This will:
    - Export the Production database  
    - Stream it into Staging  
    - Search-replace your live URL with the staging URL  
    - Flush the WP cache  

4. **Verify** by spot-checking a few pages and custom post types in your Dev site.

## ACF Field Group Syncing

### Overview

ACF field groups are automatically synced across environments via JSON files in the `acf-json` directory.

### Steps to Sync:

1. **Field group changes are automatically saved as JSON** in `acf-json` when updated in WP Admin.
2. **Pull changes** from the repo to sync ACF fields on your local environment.
3. **Commit and push** any changes after updating field groups to share them with the team.

### Hiding ACF Admin in Production:

- The ACF admin is hidden on production and non-local environments by checking `WP_ENVIRONMENT_TYPE`. It is only visible in local environments.

## Additional Information

- [Timber Documentation](https://timber.github.io/docs/)
- [Local Development](https://docs.mediaengagement.org/mediaengagement/#local-development)
