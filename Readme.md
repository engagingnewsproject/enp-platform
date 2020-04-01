# Local App Pull

Local is a program that allows you to easily set up a WordPress environment on your local computer.



1. Download and install the [WP Engine Local App](http://localwp.com/).
    1. After download
    2. Choose your platform
    3. Fill in your information
    4. Download and Save the installation package to your computer
    5. Open the installation package for Local on your computer
    6. Follow the installation setup prompts
    7. Launch Local on your computer
2. Enable wpe api and generate credentials
    8. Open the API Access page of your WP Engine User Portal:
        1. https://my.wpengine.com/api_access
    9. Locate the account name you wish to enable access for
    10. Click Manage
    11. Toggle Account API Access to on
    12. Click Generate Credentials to return to the previous page
    13. Click the Generate Credentials button at the top
    14. Leave this page open for easy access in the next step
3. Connect Local to WP Engine
    15. Open the Local application on your computer
    16. Click Connect at the top left
    17. Click LOG IN TO YOUR HOST
    18. Select WP Engine
    19. Copy and paste your WPE API credentials, from the previous step
    20. Click LOGIN TO WP ENGINE
4. Pull to Local from WP Engine

This process needs to take place the very first time you pull to Local from WP Engine.

** If you are not added as a user on the remote WP install [add your user profile via phpMyAdmin](https://wpengine.com/support/add-admin-user-phpmyadmin/).



    21. Ensure you’ve already connected Local to your WP Engine account
    22. Open Local on your computer
    23. Go the Connect tab
    24. Locate a Site in the list
    25. Click PULL TO LOCAL
    26. Choose whether or not to include the database
5. add to wp-config.php:

       `define( 'WPE_CLUSTER_ID', '0' );`


    ```
       define('WP_DEBUG', false);
       ini_set('display_errors','Off');
       ini_set('error_reporting', E_ALL );
       define('WP_DEBUG', false);
       define('WP_DEBUG_DISPLAY', false);
    ```


6. Download database from WP Engine
    27. In WP Engine Portal visit `cmengage` from [Sites tab](https://my.wpengine.com/sites)
    28. phpMyAdmin tab
    29. In phpMyAdmin click wp_cmengage tab
    30. **Export** top tab
    31. In “Exporting tables from "wp_cmengage" database” / Export method:
    32. Select “Custom” bullet
    33. Scroll to bottom of page and click go
        2. ** if timeout occurs on export select 50% of tables in “Tables:” and export, then select the final 50% and export.
7. terminal: pull database
    34. 


## Extra Info



*   update the upload max file limit.  |  | https://sitenetic.com/techie/mamp-error-phpmyadmin-error-incorrect-format-parameter/
*   composer install not allowing vendor |  | https://github.com/laravel/valet/issues/763#issuecomment-482095200
