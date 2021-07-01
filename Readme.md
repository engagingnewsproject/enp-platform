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



# Installing


1. Download and install the [WP Engine Local App](http://localwp.com/).
    *   > The Local App is a WP Engine program that allows you to easily set up a WordPress environment on your local computer. 
    *   After download choose your platform
    *   Fill in your information
    *   Download and Save the installation package to your computer
    *   Open the installation package for Local on your computer
    *   Follow the installation setup prompts
    *   Launch Local on your computer
2. Enable wpe api and generate credentials
    *   Open the API Access page of your WP Engine User Portal:
        *   [https://my.wpengine.com/api_access](https://my.wpengine.com/api_access)
    *   Locate the account name you wish to enable access for
    *   Click Manage
    *   Toggle Account API Access to on
    *   Click Generate Credentials to return to the previous page
    *   Click the Generate Credentials button at the top
    *   Leave this page open for easy access in the next step
3. Connect Local to WP Engine
    *   Open the Local application on your computer
    *   Click Connect at the top left
    *   Click LOG IN TO YOUR HOST
    *   Select WP Engine
    *   Copy and paste your WPE API credentials, from the previous step
    *   Click LOGIN TO WP ENGINE
4. Pull to Local from WP Engine
    *   This process needs to take place the very first time you pull to Local from WP Engine. ** If you are not added as a user on the remote WP install [add your user profile via phpMyAdmin](https://wpengine.com/support/add-admin-user-phpmyadmin/).
    *   Ensure you’ve already connected Local to your WP Engine account
    *   Open Local on your computer
    *   Go the Connect tab
    *   Locate a Site in the list
    *   Click PULL TO LOCAL
    *   Choose whether or not to include the database, if you include the database then you can skip steps 6 and 7
5. (Optional) In wp-config.php: keep debug warnings from displaying on the front end

    ```
    define( 'WPE_CLUSTER_ID', '0' );
    define('WP_DEBUG', false);
    ini_set('display_errors','Off');
    ini_set('error_reporting', E_ALL );
    define('WP_DEBUG', false);
    define('WP_DEBUG_DISPLAY', false);
    ```
    
    
### If you included the database in your PULL TO LOCAL then you can skip steps 6 and 7.
6. (Optional) Download database from WP Engine
    *   In WP Engine Portal visit cmengage from [Sites tab](https://my.wpengine.com/sites)
    *   phpMyAdmin tab
    *   In phpMyAdmin click wp_cmengage tab
    *   Export top tab
    *   Export method: select “Custom” bullet
    *   Scroll to bottom of page and click Go
        *   if timeout occurs on export select 50% of tables in “Tables:” and export, then select the final 50% and export.
7. (Optional) Move database download to Local socket. 
    *   Upload the database file downloaded from phpMyAdmin in step 14.

        ```
        mysql -uroot -proot -h localhost --socket "/Users/[USERNAME]/Library/Application Support/Local/run/EzKVmKywD/mysql/mysqld.sock" local < /Users/[USERNAME]/Downloads/wp_cmengage.sql
        ```

        *   `[USERNAME]` replace with your local computer username
        *   `/Users/[USERNAME]/Downloads/wp_cmengage.sql `replace path to downloaded database file in step 14.
        *   `EzKVmKywD `Local spins up a specific instance of mysql that's used only by that site - `EzKVmKywD` being a unique ID to the site you have. If you were on a production server, you'd normally just use mysql -uUSER -pPASSWORD to get connected.
            *   To find this unique ID:
                1. `cd /Users/[USERNAME]/Library/Application\ Support/Local/run/`
                2. `ls`
            *   The unique ID should be listed.
8. Edit the wp_config file
    *   Go to the line containing `/** MySQL database password */`
    *   Ensure the password and username are 'root'. The host should be `localhost`
9. Edit ~/Local Sites/mediaengagementorg/app/public/wp-content/enp-quiz-config.php
    *   Comment out the following lines inside the else
        ```
        echo 'unknown quiz config path for '.$site;
        die;
        ```
    *   This is a temporary fix for quiz-tool, if you end up working on quiz tool you will need to redo the pathing below the if structure.
10. In the Local App under the Local Sites tab click View Site button to open 
    *   the site([http://localhost:10000/](http://localhost:10000/wp-admin/)) & 
    *   Admin button to open the WP admin([http://localhost:10000/wp-admin/](http://localhost:10000/wp-admin/)).
11. Connecting/syncing with github
    *   cd into ~/Local Sites/mediaengagementorg/app/public and enter the following commands
        ```
        git init
        git remote add origin https://github.com/engagingnewsproject/enp-platform.git
        ```
    *   If you are re-adding the origin and get a `Remote origin already exists` error run:
        ```
        git remote set-url origin https://github.com/engagingnewsproject/enp-platform.git
        ```
    *   And then fetch from origin:
        ```
        git fetch --all
        git reset --hard origin/master
        ```
    *   At this point your directory should now be connected with our repo and up to date with master.
    *   Small aside, if you need to update your database, pull from WPENGINE again and include the database.
12. Browser refreshing (browsersync)
    *   cd into ~/Local Sites/mediaengagementorg/app/public/wp-content/themes/engage
    *   Enter the command `npm install`
    *   Edit webpack.mix.js to make sure the browsersync proxy field is the url of your Local site host.
    *   To view live scss or css changes while developing run `npm run watch`. Ignore the errors for now if it's working.
    *   When done developing, `^ + c`, and minify for production `npm run production`. 

## Important Links



*   [Google Doc installation instructions](https://docs.google.com/document/d/1-ZhREJ0MZ9hsnN-Hc-6bbpFlXq9b91CSfl2DfJ5IpwI/edit?usp=sharing)
*   [Center for Media Engagement Github](https://github.com/engagingnewsproject)
*   [WP Engine Portal](https://identity.wpengine.com/signin)
*   [Mastering Markdown](https://guides.github.com/features/mastering-markdown/)
*   [Google Docs to Markdown](https://github.com/evbacher/gd2md-html/wiki)
*   [Update PHP upload max file limit.](https://sitenetic.com/techie/mamp-error-phpmyadmin-error-incorrect-format-parameter/)
*   [Composer install not allowing vendor?](https://github.com/laravel/valet/issues/763#issuecomment-482095200)
*   [Using the GitHub Desktop Client](https://idratherbewriting.com/learnapidoc/pubapis_github_desktop_client.html#managing-merge-conflicts)

<!-- Docs to Markdown version 1.0β21 -->
