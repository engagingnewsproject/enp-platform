<?php

/**
 * Conditionally install and activate NF Legacy plugin
 */
class NF_LoadLegacy
{
    const LEGACY_PLUGIN_NAME = 'ninja-forms-legacy/ninja-forms-legacy.php';

    const DOWNLOAD_PREVIOUSLY_ATTEMPTED_KEY = 'ninja_forms_legacy_download_previously_attempted';

    const PLUGIN_SLUG = 'ninja-forms-legacy';

    /**
     * Check if NF Legacy plugin is active
     *
     * @var boolean
     */
    protected $isLegacyActive = false;

    /**
     * Has download of NF Legacy been previously attempted
     * 
     * If previously attempted, do NOT continue to attempt download, otherwise
     *  we are in a potentially continous loop
     *
     * @var boolean
     */
    protected $previouslyAttempted = false;

    /**
     * Conditionally install and activate NF Legacy plugin  
     */
    public function handle()
    {

        $this->checkLegacyActive();

        if ($this->isLegacyActive) {
            do_action('ninja_forms_load_legacy');
            return;
        }
        $this->checkPreviousAttempt();

        if ($this->previouslyAttempted) {
            return;
        }

        $this->installLegacy();

        $this->registerAttemptedInstallation();
    }

    /**
     * Request WP installation of NF Legacy plugin from public repository
     *
     * @return void
     */
    protected function installLegacy()
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';
        include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        include_once ABSPATH . 'wp-admin/includes/file.php';
        include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        /*
        * Use the WordPress Plugins API to get the plugin download link.
        */
        $api = plugins_api('plugin_information', array(
            'slug' => self::PLUGIN_SLUG,
        ));

        if (is_wp_error($api)) {
            exit;
        }

        /*
        * Use the AJAX Upgrader skin to quietly install the plugin.
        */
        $upgrader = new Plugin_Upgrader(new WP_Ajax_Upgrader_Skin());
        $install = $upgrader->install($api->download_link);
        if (is_wp_error($install)) {
            exit;
        }

        activate_plugin($upgrader->plugin_info());
    }

    /**
     * Check if NF Legacy plugin is active
     */
    protected function checkLegacyActive()
    {
        include_once ABSPATH . 'wp-admin/includes/plugin.php';

        $activePlugins = get_plugins();

        $this->isLegacyActive = in_array(self::LEGACY_PLUGIN_NAME, array_keys($activePlugins));

        return $this->isLegacyActive;
    }

    /**
     * Check if previously attempt to load has been made
     */
    protected function checkPreviousAttempt()
    {
        $this->previouslyAttempted = get_option(self::DOWNLOAD_PREVIOUSLY_ATTEMPTED_KEY, false);

        return $this->previouslyAttempted;
    }

    /**
     * Register that an attempt to install Legacy has been made
     */
    protected function registerAttemptedInstallation()
    {
        update_option(self::DOWNLOAD_PREVIOUSLY_ATTEMPTED_KEY, true);
    }
}
