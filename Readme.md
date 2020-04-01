# Local App Pull

Local is a program that allows you to easily set up a WordPress environment on your local computer.



1. Download and install the [WP Engine Local App](http://localwp.com/).
    *   After download
    *   Choose your platform
    *   Fill in your information
    *   Download and Save the installation package to your computer
    *   Open the installation package for Local on your computer
    *   Follow the installation setup prompts
    *   Launch Local on your computer
2. Enable wpe api and generate credentials
    *   Open the API Access page of your WP Engine User Portal:
        1. https://my.wpengine.com/api_access
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
    *   This process needs to take place the very first time you pull to Local from WP Engine.
    *   ** If you are not added as a user on the remote WP install [add your user profile via phpMyAdmin](https://wpengine.com/support/add-admin-user-phpmyadmin/).
    *   Ensure you’ve already connected Local to your WP Engine account
    *   Open Local on your computer
    *   Go the Connect tab
    *   Locate a Site in the list
    *   Click PULL TO LOCAL
    *   Choose whether or not to include the database
5. add to wp-config.php:

    ```
       define( 'WPE_CLUSTER_ID', '0' );
       define('WP_DEBUG', false);
       ini_set('display_errors','Off');
       ini_set('error_reporting', E_ALL );
       define('WP_DEBUG', false);
       define('WP_DEBUG_DISPLAY', false);
    ```


6. Download database from WP Engine
    *   In WP Engine Portal visit `cmengage` from [Sites tab](https://my.wpengine.com/sites)
    *   phpMyAdmin tab
    *   In phpMyAdmin click wp_cmengage tab
    *   **Export** top tab
    *   In “Exporting tables from "wp_cmengage" database” / Export method:
    *   Select “Custom” bullet
    *   Scroll to bottom of page and click go
        2. ** if timeout occurs on export select 50% of tables in “Tables:” and export, then select the final 50% and export.
7. terminal: pull database
    *   . . . more to come. . .


## Extra Info



*   update the upload max file limit.  |  | https://sitenetic.com/techie/mamp-error-phpmyadmin-error-incorrect-format-parameter/
*   composer install not allowing vendor |  | https://github.com/laravel/valet/issues/763#issuecomment-482095200

