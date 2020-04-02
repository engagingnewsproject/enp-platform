<!----- Conversion time: 1.155 seconds.


Using this Markdown file:

1. Cut and paste this output into your source file.
2. See the notes and action items below regarding this conversion run.
3. Check the rendered output (headings, lists, code blocks, tables) for proper
   formatting and use a linkchecker before you publish this page.

Conversion notes:

* Docs to Markdown version 1.0β21
* Thu Apr 02 2020 10:45:17 GMT-0700 (PDT)
* Source doc: CME Updated Readme.md
----->



# Installing



1. Clone the repository. Ensure that you manually add the wp_config file to the root directory once cloned.

    ```
    git clone git@github.com:engagingnewsproject/enp-platform.git mediaengagement
    ```


2. Install [homebrew](https://brew.sh/) if not already installed
3. Ensure homebrew is up to date

    ```
    brew update
    ```


4. Install PHP via brew

    ```
    brew install php@7.1
    ```


5. Install Composer and add it as an alias

    ```
    curl -sS https://getcomposer.org/installer | php
    ```


    *   Move Composer to /usr/bin/ and create an alias:

        ```
        sudo mv composer.phar /usr/local/bin/ vim ~/.bash_profile
        ```


    *   Add this to your .bash profile. It may be empty or non-existent, so go ahead and create it:

        ```
        alias composer="php /usr/local/bin/composer.phar"
        ```


    *   Make sure composer directory is in your system’s “PATH”

        ```
        export PATH=$PATH:~/.composer/vendor/bin
        ```


6. Install Valet with Composer

    ```
    composer global require laravel/valet
    valet start
    ```


7. Test the .dev TLD. The command ping foobar.test should be responding to 127.0.0.1.
    *   If the ping command is not responding, it may require a restart of terminal.
8. Install and start MySQL

    ```
    brew install mysql
    brew services start mysql
    ```


9. Download and install the [WP Engine Local App](http://localwp.com/).
    *   > The Local App is a WP Engine program that allows you to easily set up a WordPress environment on your local computer. 
    *   After download choose your platform
    *   Fill in your information
    *   Download and Save the installation package to your computer
    *   Open the installation package for Local on your computer
    *   Follow the installation setup prompts
    *   Launch Local on your computer
10. Enable wpe api and generate credentials
    *   Open the API Access page of your WP Engine User Portal:
        1. [https://my.wpengine.com/api_access](https://my.wpengine.com/api_access)
    *   Locate the account name you wish to enable access for
    *   Click Manage
    *   Toggle Account API Access to on
    *   Click Generate Credentials to return to the previous page
    *   Click the Generate Credentials button at the top
    *   Leave this page open for easy access in the next step
11. Connect Local to WP Engine
    *   Open the Local application on your computer
    *   Click Connect at the top left
    *   Click LOG IN TO YOUR HOST
    *   Select WP Engine
    *   Copy and paste your WPE API credentials, from the previous step
    *   Click LOGIN TO WP ENGINE
12. Pull to Local from WP Engine
    *   This process needs to take place the very first time you pull to Local from WP Engine. ** If you are not added as a user on the remote WP install [add your user profile via phpMyAdmin](https://wpengine.com/support/add-admin-user-phpmyadmin/).
    *   Ensure you’ve already connected Local to your WP Engine account
    *   Open Local on your computer
    *   Go the Connect tab
    *   Locate a Site in the list
    *   Click PULL TO LOCAL
    *   Choose whether or not to include the database
13. (optional) Keep debug warnings from displaying on the front end

    ```
    define( 'WPE_CLUSTER_ID', '0' );
    define('WP_DEBUG', false);
    ini_set('display_errors','Off');
    ini_set('error_reporting', E_ALL );
    define('WP_DEBUG', false);
    define('WP_DEBUG_DISPLAY', false);
    ```


14. Download database from WP Engine
    *   In WP Engine Portal visit cmengage from [Sites tab](https://my.wpengine.com/sites)
    *   phpMyAdmin tab
    *   In phpMyAdmin click wp_cmengage tab
    *   Export top tab
    *   Export method: select “Custom” bullet
    *   Scroll to bottom of page and click Go
        2. if timeout occurs on export select 50% of tables in “Tables:” and export, then select the final 50% and export.
15. Move database download to Local socket.
    *   Upload the database file downloaded from phpMyAdmin in step 14.

        ```
        mysql -uroot -proot -h localhost --socket "/Users/[USERNAME]/Library/Application Support/Local/run/EzKVmKywD/mysql/mysqld.sock" local < /Users/[USERNAME]/Downloads/wp_cmengage.sql
        ```


    *   Replace [USERNAME] with local computer username
    *   Replace `/Users/[USERNAME]/Downloads/wp_cmengage.sql`with path to downloaded database file
16. Link and secure your site. linking will ensure that the repository is linked to the domain. Securing ensures that the site is served up over HTTPS rather than the default of HTTP. Ensure you are still in the cloned directory.

    ```
    valet link mediaengagement
    valet secure mediaengagement
    ```


17. Edit the wp_config file
    *   Go to the line containing `/** MySQL database password */`
    *   Ensure the password and username are 'root'. The host should be `local)host`
18. In the Local App under the Local Sites tab click View Site button to open 
    *   the site([http://localhost:10000/](http://localhost:10000/wp-admin/)) & 
    *   Admin button to open the WP admin([http://localhost:10000/wp-admin/](http://localhost:10000/wp-admin/)).


## Important Links



*   [Google Doc installation instructions](https://docs.google.com/document/d/1-ZhREJ0MZ9hsnN-Hc-6bbpFlXq9b91CSfl2DfJ5IpwI/edit?usp=sharing)
*   [Center for Media Engagement Github](https://github.com/engagingnewsproject)
*   [WP Engine Portal](https://identity.wpengine.com/signin)
*   [Mastering Markdown](https://guides.github.com/features/mastering-markdown/)
*   [Google Docs to Markdown](https://github.com/evbacher/gd2md-html/wiki)
*   [Update PHP upload max file limit.](https://sitenetic.com/techie/mamp-error-phpmyadmin-error-incorrect-format-parameter/)
*   [Composer install not allowing vendor?](https://github.com/laravel/valet/issues/763#issuecomment-482095200)

<!-- Docs to Markdown version 1.0β21 -->
