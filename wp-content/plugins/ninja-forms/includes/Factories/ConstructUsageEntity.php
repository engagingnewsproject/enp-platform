<?php

namespace NinjaForms\Includes\Factories;

use NinjaForms\Includes\Entities\Usage;

class ConstructUsageEntity
{
    /**
     * Ninja Forms database version
     *
     * @var string
     */
    protected $nFdBVersion;


    /**
     * Indexed array of stored forms
     *
     * keys: id, title
     * 
     * @var array
     */
    protected $forms = [];

    /**
     * Time spent making NF Forms request in microseconds
     *
     * @var integer
     */
    protected $formsRequestTime = 0;

    /**
     * Total number of forms
     *
     * @var integer
     */
    protected $countForms = 1;

    /**
     * Indexed array of form settings
     *
     * keys: 
     * @var array
     */
    protected $formMeta = [];

    /**
     * Time spent making NF form settings request in microseconds
     *
     * @var integer
     */
    protected $formMetaRequestTime = 0;

    /**
     * Indexed array of fields
     *
     * keys: id, type, parent_id
     * 
     * @var array
     */
    protected $fields = [];

    /**
     * Time spent making NF fields request in microseconds
     *
     * @var integer
     */
    protected $fieldsRequestTime = 0;

    /**
     * Indexed array of field settings
     *
     * keys: 
     * @var array
     */
    protected $fieldMeta = [];

    /**
     * Time spent making NF field settings request in microseconds
     *
     * @var integer
     */
    protected $fieldMetaRequestTime = 0;

    /**
     * Indexed array of active stored actions
     *
     * keys: id, type, parent_id
     * @var array
     */
    protected $actions = [];

    /**
     * Time spent making NF actions request in microseconds
     *
     * @var integer
     */
    protected $actionsRequestTime = 0;

    /**
     * 
     *
     * @var array
     */
    protected $calculations = [];


    /**
     * Array of stored NF settings
     *
     * key-value pairs of all Ninja Forms settings stored in options table
     *
     * @var array
     */
    protected $ninjaFormsSettings = [];

    /**
     * Time spent making NF settings request in microseconds
     *
     * @var integer
     */
    protected $ninjaFormsSettingsRequestTime = 0;

    /**
     * Wordpress global DB object
     *
     * @var object
     */
    protected $wpdb;

    /**
     * Construct with NF database version
     *
     * @param string|null $nFdBVersion
     */
    public function __construct(?string $nFdBVersion = '1.4')
    {

        $this->nFdBVersion = $nFdBVersion;
    }

    /**
     * Return constructed site environment entity
     *
     * @return Usage
     */
    public function handle(): Usage
    {
        $this->populateWpdb();

        $this->populateForms();

        $this->populateFormMeta();

        $this->populateFields();

        $this->populateFieldMeta();

        $this->populateActions();

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
            'field_settings' => $this->constructFieldMetaUsage(),
            'actions' => $this->constructActionsUsage(),
            'display_settings' => $this->constructDisplaySettingsUsage(),
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
     * Activation, deactivation, time between activation and deactivation
     * @return array
     */
    protected function constructPluginUsage(): array
    {
        return [];
    }

    /**
     * Construct array of forms usage
     *
     * Number of forms, forms appended to a post or page, forms with public link
     * enabled
     * 
     * @return array
     */
    protected function constructFormsUsage(): array
    {
        $return = [
            'formCount' => $this->countForms,
            't' => $this->formsRequestTime
        ];

        return $return;
    }

    /**
     * Construct array of fields usage
     *
     * Data, current and planned:
     * - mean fields per form
     * - mean each field type per form
     * 
     * @return array
     */
    protected function constructFieldsUsage(): array
    {
        $return = [
            'fieldsPerForm' => $this->calculateFieldsPerForm(),
            'fieldTypesPerForm' => $this->calculateFieldTypesCountPerForm(),
            't' => $this->fieldsRequestTime
        ];

        return $return;
    }

    /**
     * Calculate fields per form
     *
     * @return float
     */
    protected function calculateFieldsPerForm(): float
    {
        $return = round(count($this->fields) / $this->countForms, 1);

        return $return;
    }

    /**
     * Calculate count of each field type per form
     *
     * key-value pairs fieldType:countOfFieldTypePerForm
     * 
     * @return array
     */
    protected function calculateFieldTypesCountPerForm(): array
    {
        $return = [];

        $typeColumn = \array_column($this->fields, 'type');
        $typesCount = \array_count_values($typeColumn);

        foreach ($typesCount as $type => $count) {

            $return[$type] = round($count / $this->countForms, 1);
        }

        return $return;
    }

    /**
     * Construct array of field settings usage
     *
     * Data, current and planned:
     * - custom class name wrapper - empty/not empty
     * - custom class name element - empty/not empty
     * - label position - default/not default
     * - custom name attribute - empty/not empty
     * - field key - default/not default
     * - admin label - empty/not empty
     * 
     * @return array
     */
    protected function constructFieldMetaUsage(): array
    {
        $startTime = microtime(true);
        $totalFields = count($this->fields);

        $arrayColumnKeys = array_column($this->fieldMeta, 'key');
        $countArrayKeys = \array_count_values($arrayColumnKeys);

        $labelPositions = [];

        foreach ($this->fieldMeta as $settingArray) {
            if ('label_pos' === $settingArray['key']) {
                $labelPositions[] = $settingArray['value'];
            }
        }

        $countLabelPositions = \array_count_values($labelPositions);

        return [
            'countTotalFields' => $totalFields,
            'countCustomClassNameWrapper' => isset($countArrayKeys['wrapper_class']) ? $countArrayKeys['wrapper_class'] : 0,
            'countCustomClassNameElement' => isset($countArrayKeys['element_class']) ? $countArrayKeys['element_class'] : 0,
            'label_position' => $countLabelPositions,
            'countCustomName' => isset($countArrayKeys['custom_name_attribute']) ? $countArrayKeys['custom_name_attribute'] : 0,
            'countAdminLabel' => isset($countArrayKeys['admin_label']) ? $countArrayKeys['admin_label'] : 0,
            't' => $this->fieldMetaRequestTime,
            't2' => $this->calculateElapsedTime($startTime)
        ];
    }

    /**
     * Construct array of actions usage
     * 
     * Data, current and planned:
     * - active actions per form
     * - active action types per form
     * - reCaptcha action active on at least one form - true/false
     * - Akisment action active on at least one - true/false
     * - Delete Data action active on at least one - true/false
     * - Export Data action active on at least one - true/false
     * - WP Hook action active on at least one - true/false
     * - Record Submissions action active on at least one form - true/false
     * - - Designated submitter's email address value set
     * - - Save All or Save None
     * - - Except - empty/not empty
     * - - Set Submissions to Expire - true/false
     * 
     * @return array
     */
    protected function constructActionsUsage(): array
    {
        $actionTypesCountPerForm = $this->calculateActionTypesCountPerForm();

        $return = [
            'actionsPerForm' => $this->calculateActionsPerForm(),
            'actionTypesPerForm' => $actionTypesCountPerForm,
            'reCaptchaActionActive' => isset($actionTypesCountPerForm['recaptcha']) ? true : false,
            'akismetActionActive' => isset($actionTypesCountPerForm['akismet']) ? true : false,
            'deleteDataActionActive' => isset($actionTypesCountPerForm['deletedatarequest']) ? true : false,
            'exportActionActive' => isset($actionTypesCountPerForm['exportdatarequest']) ? true : false,
            'wpHookActionActive' => isset($actionTypesCountPerForm['custom']) ? true : false,
            'recordSubmissionsActionActive' => isset($actionTypesCountPerForm['save']) ? true : false,
            't' => $this->actionsRequestTime
        ];

        return $return;
    }

    /**
     * Calculate actions per form
     *
     * @return float
     */
    protected function calculateActionsPerForm(): float
    {
        $return = round(count($this->actions) / $this->countForms, 1);

        return $return;
    }

    /**
     * Calculate count of each action type per form
     *
     * key-value pairs actionType:countOfActionTypePerForm
     * 
     * @return array
     */
    protected function calculateActionTypesCountPerForm(): array
    {
        $return = [];

        $typeColumn = \array_column($this->actions, 'type');
        $typesCount = \array_count_values($typeColumn);

        foreach ($typesCount as $type => $count) {

            $return[$type] = round($count / $this->countForms, 1);
        }

        return $return;
    }

    /**
     * Construct array of display settings usage
     * 
     * Data, current and planned:
     * - Display form title - count true
     * - Clear successfully completed form - count true
     * - Hide successfully completed form - count true
     * - Default label position - count each position
     * - Wrapper class - count custom
     * - Element class - count custom
     * - Form title heading level - count each value
     * - Email error message - count custom
     * - Date error message - count custom
     * - Field error message - count custom
     * - Num Min error message - count custom
     * - Num Max error message - count custom
     * - Num IncrementBy error message - count custom
     * - Correct Errors error message - count custom
     * - Validate Required Field error message - count custom
     * - Honeypot error message - count custom
     * - Field Marked Required error message - count custom
     * 
     * @return array
     */
    protected function constructDisplaySettingsUsage(): array
    {
        $arrayColumnKeys = array_column($this->formMeta, 'key');
        $countArrayKeys = \array_count_values($arrayColumnKeys);

        $defaultLabelPos = [];
        $formTitleHeadingLevel = [];

        foreach ($this->formMeta as $metaArray) {

            if ('default_label_pos' === $metaArray['key']) {
                $defaultLabelPos[] = $metaArray['value'];
            }

            if ('form_title_heading_level' === $metaArray['key']) {
                $formTitleHeadingLevel[] = $metaArray['value'];
            }
        }

        return [
            'displayFormTitle' => isset($countArrayKeys['show_title']) ? $countArrayKeys['show_title'] : 0,
            'clearComplete' => isset($countArrayKeys['clear_complete']) ? $countArrayKeys['clear_complete'] : 0,
            'hideComplete' => isset($countArrayKeys['hide_complete']) ? $countArrayKeys['hide_complete'] : 0,
            'defaultLabelPos' => \array_count_values($defaultLabelPos),
            'countCustomClassNameWrapper' => isset($countArrayKeys['wrapper_class']) ? $countArrayKeys['wrapper_class'] : 0,
            'countCustomClassNameElement' => isset($countArrayKeys['element_class']) ? $countArrayKeys['element_class'] : 0,
            'formTitleHeadingLevel' => \array_count_values($formTitleHeadingLevel),
            'countChangeEmailErrorMessage' => isset($countArrayKeys['changeEmailErrorMsg']) ? $countArrayKeys['changeEmailErrorMsg'] : 0,
            'countChangeDateErrorMsg' => isset($countArrayKeys['changeDateErrorMsg']) ? $countArrayKeys['changeDateErrorMsg'] : 0,
            'countConfirmFieldErrorMsg' => isset($countArrayKeys['confirmFieldErrorMsg']) ? $countArrayKeys['confirmFieldErrorMsg'] : 0,
            'countFieldNumberNumMinError' => isset($countArrayKeys['fieldNumberNumMinError']) ? $countArrayKeys['fieldNumberNumMinError'] : 0,
            'countFieldNumberNumMaxError' => isset($countArrayKeys['fieldNumberNumMaxError']) ? $countArrayKeys['fieldNumberNumMaxError'] : 0,
            'countFieldNumberIncrementBy' => isset($countArrayKeys['fieldNumberIncrementBy']) ? $countArrayKeys['fieldNumberIncrementBy'] : 0,
            'countFormErrorsCorrectErrors' => isset($countArrayKeys['formErrorsCorrectErrors']) ? $countArrayKeys['formErrorsCorrectErrors'] : 0,
            'countValidateRequiredField' => isset($countArrayKeys['validateRequiredField']) ? $countArrayKeys['validateRequiredField'] : 0,
            'countHoneypotHoneypotError' => isset($countArrayKeys['honeypotHoneypotError']) ? $countArrayKeys['honeypotHoneypotError'] : 0,
            'countFieldsMarkedRequired' => isset($countArrayKeys['fieldsMarkedRequired']) ? $countArrayKeys['fieldsMarkedRequired'] : 0,
            't'=>$this->formMetaRequestTime
        ];
    }

    /**
     * Construct array of restrictions usage
     *
     * Data, current and planned:
     * - Unique field usage count
     * - Non-default unique field error message count
     * - Require users to be logged in count
     * - Non-default must-be-logged-in message count
     * - Submission limit usage count
     * - Non-default submission limit message count
     * 
     * @return array
     */
    protected function constructRestrictionsUsage(): array
    {
        $arrayColumnKeys = array_column($this->formMeta, 'key');
        $countArrayKeys = \array_count_values($arrayColumnKeys);

        return [
            'uniqueFieldUsage' => isset($countArrayKeys['unique_field']) ? $countArrayKeys['unique_field'] : 0,
            'nonDefaultUniqueFieldMessage'=>isset($countArrayKeys['unique_field_error']) ? $countArrayKeys['unique_field_error'] : 0,
            'usersLoggedInCount' => isset($countArrayKeys['logged_in']) ? $countArrayKeys['logged_in'] : 0,
            'nonDefaultLoggedInMessage'=>isset($countArrayKeys['not_logged_in_msg']) ? $countArrayKeys['not_logged_in_msg'] : 0,
            'submissionLimitUsage' => isset($countArrayKeys['sub_limit_number']) ? $countArrayKeys['sub_limit_number'] : 0,
            'nonDefaultSubmissionLimitMessage' => isset($countArrayKeys['sub_limit_msg']) ? $countArrayKeys['sub_limit_msg'] : 0,
        ];
    }

    /**
     * Construct array of calculations usage
     *
     * @return array
     */
    protected function constructCalculationsUsage(): array
    {
        $return = [];

        foreach($this->formMeta as $metaArray){

            if('calculations'==$metaArray['key']){

                $formCalculationData = [];

                $calculationsForForm = unserialize($metaArray['value'],['allowed_classes'=>false]);

                if(empty($calculationsForForm)){
                    continue;
                }

                $equations = \array_column($calculationsForForm,'eq');

                $formCalculationData['calculationCount']= \count($equations);      

                foreach($equations as $equation){
                    $formCalculationData['mergeTagCount'][] = \substr_count($equation,'{field:');
                }

                $return[]=$formCalculationData;
            }
        }

        return $return;
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
            't' => $this->ninjaFormsSettingsRequestTime
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
     * Calculate elapsed time from given start time in microseconds
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

    /**
     * Populate $wpdb with global WordPress DB object
     *
     * @return void
     */
    protected function populateWpdb(): void
    {
        global $wpdb;

        $this->wpdb = $wpdb;
    }

    /**
     * Populate Forms data
     *
     * @return void
     */
    protected function populateForms(): void
    {
        $startTime = microtime(true);

        $sql = '
        SELECT id, title
        FROM ' . $this->wpdb->prefix . 'nf3_forms
        ;';

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        $this->forms = $results;

        $this->formsRequestTime = $this->calculateElapsedTime($startTime);

        $this->countForms = count($this->forms);
    }

    /**
     * Populate Form Meta data
     *
     * @return void
     */
    protected function populateFormMeta(): void
    {
        $startTime = microtime(true);

        $sql = $this->constructFormMetaSql();

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        $this->formMeta = $results;

        $this->formMetaRequestTime = $this->calculateElapsedTime($startTime);
    }

    /**
     * SQL for form meta data
     *
     * @return string
     */
    protected function constructFormMetaSql(): string
    {
        $defaultUniqueFieldError ='A form with this value has already been submitted.';	
        $defaultSubmissionLimitMessage = 'The form has reached its submission limit.';
        
        $return = '
        SELECT `id`, `parent_id`, `key`, `value`
        FROM ' . $this->wpdb->prefix . 'nf3_form_meta
        WHERE 
            (`key` = "show_title" AND ( `value` = "1"))
            OR (`key` = "clear_complete" AND ( `value` = "1"))
            OR (`key` = "hide_complete" AND ( `value` = "1"))
            OR (`key` = "allow_public_link" AND ( `value` = "1"))
            OR (`key` = "default_label_pos") 
            OR (`key` = "wrapper_class" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "element_class" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "form_title_heading_level") 
            OR (`key` = "changeEmailErrorMsg" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "changeDateErrorMsg" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "confirmFieldErrorMsg" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "fieldNumberNumMinError" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "fieldNumberNumMaxError" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "fieldNumberIncrementBy" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "formErrorsCorrectErrors" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "validateRequiredField" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "honeypotHoneypotError" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "fieldsMarkedRequired" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "unique_field_error" AND (`value` <> "'.$defaultUniqueFieldError.'")) 
            OR (`key` = "unique_field" AND (`value` is NOT null AND `value` <> ""))
            OR (`key` = "logged_in" AND (`value` is NOT null AND `value` <> ""))
            OR (`key` = "not_logged_in_msg" AND (`value` is NOT null AND `value` <> ""))
            OR (`key` = "sub_limit_number") 
            OR (`key` = "sub_limit_msg" AND (`value` <> "'.$defaultSubmissionLimitMessage.'"))
            OR (`key` = "calculations")
        ;';


        if ($this->nFdBVersion < '1.3') {
            $return =  str_replace(['`key`', '`value`'], ['`meta_key`', '`meta_value`'], $return);
            $return =  str_replace(['as `meta_key`', 'as `meta_value`'], ['as `key`', 'as `value`'], $return);
        }

        return $return;
    }

    /**
     * Populate Fields 
     *
     * @return void
     */
    protected function populateFields(): void
    {
        $startTime = microtime(true);

        $sql = '
        SELECT id, type, parent_id
        FROM ' . $this->wpdb->prefix . 'nf3_fields
        ;';

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        $this->fields = $results;

        $this->fieldsRequestTime = $this->calculateElapsedTime($startTime);
    }

    /**
     * Populate Field Settings
     *
     * @return void
     */
    protected function populateFieldMeta(): void
    {
        $startTime = microtime(true);

        $sql = $this->constructFieldMetaSql();

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        $this->fieldMeta = $results;

        $this->fieldMetaRequestTime = $this->calculateElapsedTime($startTime);
    }

    /**
     * SQL for field meta data
     * 
     * Modify for NF DB Version < 1.3
     *
     * @return string
     */
    protected function constructFieldMetaSql(): string
    {
        $return = '
        SELECT `id`, `parent_id`, `key` as `key`, `value` as `value`
        FROM ' . $this->wpdb->prefix . 'nf3_field_meta
        WHERE 
            (`key` = "label_pos")
            OR (`key` = "wrapper_class" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "element_class" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "custom_name_attribute" AND (`value` is NOT null AND `value` <> "")) 
            OR (`key` = "admin_label" AND (`value` is NOT null AND `value` <> "")) 
        ;';

        if ($this->nFdBVersion < '1.3') {
            $return =  str_replace(['`key`', '`value`'], ['`meta_key`', '`meta_value`'], $return);
            $return =  str_replace(['as `meta_key`', 'as `meta_value`'], ['as `key`', 'as `value`'], $return);
        }

        return $return;
    }

    /**
     * Populate NfActions 
     *
     * @return void
     */
    protected function populateActions(): void
    {
        $startTime = microtime(true);

        $sql = '
        SELECT id, type, parent_id
        FROM ' . $this->wpdb->prefix . 'nf3_actions
        WHERE active = 1
        ;';

        $results = $this->wpdb->get_results($sql, ARRAY_A);

        $this->actions = $results;

        $this->actionsRequestTime = $this->calculateElapsedTime($startTime);
    }

    /**
     * Populate ninja_forms_settings $ninjaFormsSettings
     *
     * @return void
     */
    protected function populateNinjaFormsSettings(): void
    {
        $startTime = microtime(true);

        $nfSettingsFromOptions = get_option('ninja_forms_settings', []);

        $this->ninjaFormsSettings = $nfSettingsFromOptions;

        $this->ninjaFormsSettingsRequestTime = $this->calculateElapsedTime($startTime);
    }
}
