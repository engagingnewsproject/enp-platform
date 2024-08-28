<?php

namespace NinjaForms\Includes\Factories;

use NinjaForms\Includes\Entities\Usage;

class ConstructUsageEntity
{
    /**
     * Array of stored NF settings
     *
     * @var array
     */
    protected $ninjaFormsSettings = [];

    /**
     * Time spent making NF settings request in milliseconds
     *
     * @var integer
     */
    protected $ninjaFormsSettingsRequestTime = 0;

    /**
     * Return constructed site environment entity
     *
     * @return Usage
     */
    public function handle(): Usage
    {
        $this->populateNinjaFormsSettings();

        $array = $this->constructUsageArray();

        $return = Usage::fromArray($array);

        return $return;
    }

    /**
     * Construct usage data
     *
     * @return array
     */
    protected function constructUsageArray(): array
    {
        $return = array(
            'plugin' => $this->constructPluginUsage(),
            'forms' => $this->constructFormsUsage(),
            'fields' => $this->constructFieldsUsage(),
            'fieldSettings' => $this->constructFieldSettingsUsage(),
            'actions' => $this->constructActionsUsage(),
            'displaySettings' => $this->constructDisplaySettingsUsage(),
            'restrictions' => $this->constructRestrictionsUsage(),
            'calculations' => $this->constructCalculationsUsage(),
            'submissions' => $this->constructSubmissionsUsage(),
            'settings' => $this->constructSettingsUsage()
        );

        return $return;
    }

    /**
     * Construct array of plugin usage
     *
     * @return array
     */
    protected function constructPluginUsage(): array
    {
        return [];
    }

    /**
     * Construct array of forms usage
     *
     * @return array
     */
    protected function constructFormsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of fields usage
     *
     * @return array
     */
    protected function constructFieldsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of field settings usage
     *
     * @return array
     */
    protected function constructFieldSettingsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of actions usage
     *
     * @return array
     */
    protected function constructActionsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of display settings usage
     *
     * @return array
     */
    protected function constructDisplaySettingsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of restrictions usage
     *
     * @return array
     */
    protected function constructRestrictionsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of calculations usage
     *
     * @return array
     */
    protected function constructCalculationsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of submissions usage
     *
     * @return array
     */
    protected function constructSubmissionsUsage(): array
    {
        return [];
    }

    /**
     * Construct array of settings usage
     *
     * @return array
     */
    protected function constructSettingsUsage(): array
    {
        return [
            'opinionatedStyles' => $this->getNfSettingByKey('opinionated_styles', ''),
            'disableAdminNotices' => $this->getNfSettingByKey('disable_admin_notices', '0'),
            'loadLegacySubmissions' => $this->getNfSettingByKey('load_legacy_submissions', '0'),
            't'=>$this->ninjaFormsSettingsRequestTime
        ];
    }

    /**
     * Get ninja_forms_settings by key
     *
     * @param string $key
     * @param string $fallback
     * @return mixed
     */
    protected function getNfSettingByKey(string $key, $fallback = '')
    {
        $return = isset($this->ninjaFormsSettings[$key])
            ? $this->ninjaFormsSettings[$key]
            : $fallback;

        return $return;
    }

    /**
     * Populate ninja_forms_settings
     *
     * @return void
     */
    protected function populateNinjaFormsSettings(): void
    {
        if (empty($this->ninjaFormsSettings)) {

            $startTime = microtime(true);

            $nfSettingsFromOptions = get_option('ninja_forms_settings', []);

            $this->ninjaFormsSettings = $nfSettingsFromOptions;

            $this->ninjaFormsSettingsRequestTime = $this->calculateElapsedTime($startTime);
        }
    }

    /**
     * Calculate elapsed time from given start time in milliseconds
     *
     * @param float $startTime
     * @return integer
     */
    protected function calculateElapsedTime(float $startTime): int
    {
        $endTime = microtime(true);

        $elapsed = $endTime - $startTime;
        
        $microseconds = (int)($elapsed * 1000000);

        return $microseconds;
    }
}
