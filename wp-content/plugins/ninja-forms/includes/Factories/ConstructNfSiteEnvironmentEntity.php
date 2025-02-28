<?php

namespace NinjaForms\Includes\Factories;

use NinjaForms\Includes\Entities\NfSiteEnvironment;
use \Ninja_Forms;

class ConstructNfSiteEnvironmentEntity
{

    /**
     * Return constructed site environment entity
     *
     * @return NfSiteEnvironment
     */
    public function handle(): NfSiteEnvironment
    {
        $array = $this->constructEnvironmentVariableArray();

        $return = NfSiteEnvironment::fromArray($array);

        return $return;
    }

    /**
     * Construct environment variable array
     *
     * @return array
     */
    protected function constructEnvironmentVariableArray(): array
    {
        $return = [
            'nf_version'                => $this->getNinjaFormsVersion(),
            'nf_db_version'             => $this->getNfDbVersion(),
            'wp_version'                => $this->getWpVersion(),
            'multisite_enabled'         => $this->isMultisiteEnabled(),
            'server_type'               => $this->getServerType(),
            'php_version'               => $this->getPhpVersion(),
            'mysql_version'             => $this->getSqlVersion(),
            'wp_memory_limit'           => $this->getWpMemoryLimit(),
            'wp_debug_mode'             => $this->isWpDebugOn(),
            'wp_lang'                   => $this->getWpLang(),
            'wp_max_upload_size'        => $this->getMaxUploadSize(),
            'php_max_post_size'         => $this->getPhpPostMaxSize(),
            'hostname'                  => $this->getHostName(),
            'smtp'                      => $this->getPhpSmtp(),
            'smtp_port'                 => $this->getPhpSmtpPort(),
            'site_timezone'             => $this->getSiteTimezone(),
            'nf_gatekeeper'             => $this->getNfGatekeeper(),
            'siteTheme'                 => $this->getSiteTheme(),
            'active_plugins'            => $this->getActivePlugins()
        ];

        return $return;
    }


    /**
     * Get NinjaForms VERSION constant
     *
     * @return string
     */
    protected function getNinjaFormsVersion(): string
    {
        $default = 'unknown';

        $return = Ninja_Forms::VERSION;

        if (!is_string($return)) {
            $return = $default;
        }

        return $return;
    }

    /**
     * Get NF DB version
     *
     * @return string
     */
    protected function getNfDbVersion(): string
    {
        $default = '1.0';

        $return = get_option('ninja_forms_db_version', $default);

        // ensure returned value is string, even if stored value is not
        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get WP version as defined by get_bloginfo
     *
     * @return string
     */
    protected function getWpVersion(): string
    {
        $default = 'unknown';

        $return = get_bloginfo('version');

        if (!is_string($return)) {
            $return = $default;
        }

        return $return;
    }

    /**
     * Return value of WP's is_multisite() function
     *
     * Default is 0
     * 
     * @return boolean
     */
    protected function isMultisiteEnabled(): int
    {
        $return = 0;
        if (is_multisite()) {
            $return = 1;
        }

        return $return;
    }

    /**
     * Get server type as defined by SERVER superglobal
     *
     * @return string
     */
    protected function getServerType(): string
    {
        $default = 'unknown';

        $return = $_SERVER['SERVER_SOFTWARE'];

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get PHP version
     *
     * @return string
     */
    protected function getPhpVersion(): string
    {
        $default = 'unknown';

        $return = phpversion();

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get SQL version
     *
     * @return string
     */
    protected function getSqlVersion(): string
    {
        global $wpdb;

        $default = 'unknown';

        $return = $wpdb->db_version();

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get WP_MEMORY_LIMIT constant
     *
     * @return string
     */
    protected function getWpMemoryLimit(): string
    {
        $return = 'unknown';

        if (defined('WP_MEMORY_LIMIT') && is_string(WP_MEMORY_LIMIT)) {

            $return = WP_MEMORY_LIMIT;
        }

        return $return;
    }

    /**
     * Is WP debug set to true
     *
     * @return integer
     */
    protected function isWpDebugOn(): int
    {
        $return =  0;

        //WP_DEBUG
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $return = 1;
        }

        return $return;
    }

    /**
     * Get WP's LANG constant
     *
     * @return string
     */
    protected function getWpLang(): string
    {

        $return = 'default';

        if (defined('WPLANG') && is_string(WPLANG)) {
            $return = WPLANG;
        }

        return $return;
    }

    /**
     * Get max upload size defined by WP
     *
     * @return string
     */
    protected function getMaxUploadSize(): string
    {
        $default = 'unknown';

        $return = size_format(wp_max_upload_size());

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get PHP post_max_size configuration value
     *
     * @return string
     */
    protected function getPhpPostMaxSize(): string
    {
        $default = 'unknown';

        $return = ini_get('post_max_size');

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Construct HostName
     *
     * @return string
     */
    protected function getHostName(): string
    {
        $return = 'unknown';

        $ip_address = '';

        if (array_key_exists('SERVER_ADDR', $_SERVER)) {
            $ip_address = $_SERVER['SERVER_ADDR'];
        } else if (array_key_exists('LOCAL_ADDR', $_SERVER)) {
            $ip_address = $_SERVER['LOCAL_ADDR'];
        }

        // If we have a valid IP Address...
        if (filter_var($ip_address, FILTER_VALIDATE_IP)) {
            // Get the hostname.
            $maybeReturn = gethostbyaddr($ip_address);
        } else {
            $maybeReturn = false;
        }

        if ($maybeReturn) {
            $return = $maybeReturn;
        }

        return $return;
    }

    /**
     * Get PHP SMTP configuration value
     *
     * @return string
     */
    protected function getPhpSmtp(): string
    {
        $default = 'unknown';

        $return = ini_get('SMTP');

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get PHP SMTP PORT configuration value
     *
     * @return string
     */
    protected function getPhpSmtpPort(): string
    {
        $default = 'unknown';

        $return = ini_get('smtp_port');

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Active plugins as defined by WP's stored option
     *
     * @return array
     */
    protected function getActivePlugins(): array
    {
        $default = [];

        $return = (array) get_option('active_plugins', []);

        if (!is_array($return)) {
            $return = $default;
        }

        return $return;
    }

    /**
     * Get Site Timezone
     *
     * @return string
     */
    protected function getSiteTimezone(): string
    {
        $default = 'unknown';

        $return = get_option('timezone_string', $default);

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get Site Theme Name
     *
     * @return string
     */
    protected function getSiteTheme(): string
    {
        $default = 'unknown';

        $return = wp_get_theme()->get('Name');

        if (!is_string($return)) {

            $return = $default;
        }

        return $return;
    }

    /**
     * Get NF Gatekeeper
     *
     * @return integer
     */
    protected function getNfGatekeeper(): int
    {
        $default = '100';

        $return = get_option( 'ninja_forms_zuul', $default);

        return $return;
    }
}
