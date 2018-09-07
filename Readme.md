# Installation

1. Clone the repository. Ensure that you manually add the wp_config file to the root directory once cloned.
    ```BASH
    git clone git@github.com:engagingnewsproject/enp-platform.git mediaengagement
    ```

2. Install [homebrew](https://brew.sh/) if not already installed

3. Ensure homebrew is up to date
    ```BASH
    brew update
    ```

4. Install PHP via brew
    ```BASH
    brew install php@7.1
    ```

5. Install Composer and add it as an alias
    ```BASH
    curl -sS https://getcomposer.org/installer | php
    ```
    * Move Composer to /usr/bin/ and create an alias:
        ```BASH
        sudo mv composer.phar /usr/local/bin/ vim ~/.bash_profile
        ```
    * Add this to your .bash profile. It may be empty or non-existent, so go ahead and create it:
        ```BASH
        alias composer="php /usr/local/bin/composer.phar"
        ```
    * Make sure composer directory is in your system’s “PATH”
        ```BASH
        export PATH=$PATH:~/.composer/vendor/bin
        ```
6. Install Valet with Composer
    ```BASH
    composer global require laravel/valet
    ```
7. Test the .dev TLD. The command `ping foobar.dev` should be responding to `127.0.0.1`.

8. Install and start MySQL
    ```BASH
    brew install mysql
    ```
    ```BASH
    brew services start mysql
    ```
9. Login to MySQL
    ```BASH
    mysql -u root
    ```
10. Create and import database to use for local development. Also ensure that the Wordpress settings recognize the new URL as the correct address
    ```SQL
    CREATE DATABASE wp_cmengage;
    use wp_cmengage;
    SET autocommit=0; source (path to file here, i.e. Desktop/db.sql);
    UPDATE wp_options SET option_value = 'https://mediaengagement.test' WHERE option_name = 'siteurl';
    UPDATE wp_options SET option_value = 'https://mediaengagement.test/' WHERE option_name = 'home';
    COMMIT;
    ```
11. Link and secure your site. linking will ensure that the repository is linked to the domain. Securing ensures that the site is served up over HTTPS rather than the default of HTTP. Ensure you are still in the cloned directory
    ```BASH
    valet link mediaengagement
    valet secure mediaengagement
    ```
12. Edit the wp_config file
    * Go to the line containing `/** MySQL database password */`
    * Ensure the password is an empty string and that the username is 'root'

13. Change the PHP.ini settings. This allows us to use PHP short tags.
    * Open `/usr/local/etc/php/7.1/php.ini`
    * Search for the variable `short_open_tag` and set it to `On`.
    * Run these commands to ensure it gets activated.
        ```BASH
        valet stop
        brew services stop php71
        ```
    * Run `php-fpm -i` to output the settings for php and search for `short_open_tag => On => On` to make sure it worked.

14. Change MySQL settings
    * Open `/usr/local/etc/my.cnf`
    * Add to the bottom of the file:  
        ``` CNF
        # Disable STRICT_TRANS_TABLES (and everything else)`
        sql_mode=""
        ```
    * Run `valet stop` and `brew services stop mysql`
    * Run `valet start` to get up and going again.
    * To verify it worked, log in to your mysql server `mysql -uroot` and run `SELECT @@GLOBAL.sql_mode, @@SESSION.sql_mode;`. If everything worked, you should see:
        ``` SQL
        +--------------------------------+--------------------------------+
        | @@GLOBAL.sql_mode | @@SESSION.sql_mode |
        +--------------------------------+--------------------------------+
        |                                            |                                           |
        +--------------------------------+--------------------------------+
        1 row in set (0.00 sec)
        ```
15. Navigate to https://mediaengagement.test and your site should be up and running
