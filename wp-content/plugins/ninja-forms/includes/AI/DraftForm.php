<?php

namespace NinjaForms\Includes\AI;

/**
 * Storage-free form adapter used by the builder assistant.
 *
 * The adapter applies the same normalization as the persisted Abilities API,
 * while delaying all browser state changes until an entire model exchange has
 * completed successfully.
 */
class DraftForm
{
    /** @var int Open form ID. */
    private int $formId;

    /** @var array Browser form snapshot. */
    private array $draft;

    /** @var bool Whether an operation changed the draft. */
    private bool $changed = false;

    /** @var int Next temporary row ID. */
    private int $nextId = -1;

    /**
     * Initialize a storage-free adapter for one open form.
     *
     * @param int   $formId Open form ID.
     * @param array $draft  Browser form snapshot.
     * @return void
     */
    public function __construct(int $formId, array $draft)
    {
        $this->formId = $formId;
        $this->draft = $this->normalizeDraft($draft);

        foreach (array_merge($this->draft['fields'], $this->draft['actions']) as $row) {
            // Inclusive: an inbound row already holding nextId must push the
            // allocator past it, or the next allocation collides with the very
            // row this scan exists to avoid.
            if (isset($row['id']) && is_numeric($row['id']) && (int) $row['id'] <= $this->nextId) {
                $this->nextId = (int) $row['id'] - 1;
            }
        }
    }

    /**
     * Execute one declared chat ability against the draft.
     *
     * @param string $abilityName Ability identifier.
     * @param array  $input       Provider-supplied arguments.
     * @return array|\WP_Error
     */
    public function execute(string $abilityName, array $input)
    {
        if (isset($input['form_id']) && (int) $input['form_id'] !== $this->formId) {
            return new \WP_Error(
                'wrong_form',
                __('The assistant can only edit the open form.', 'ninja-forms')
            );
        }

        switch ($abilityName) {
            case 'ninjaforms/get-form':
                return $this->getForm($input);
            case 'ninjaforms/list-field-types':
                return function_exists('ninja_forms_ability_list_field_types')
                    ? ninja_forms_ability_list_field_types($input)
                    : new \WP_Error(
                        'unavailable',
                        __('Field types are unavailable.', 'ninja-forms')
                    );
            case 'ninjaforms/add-field':
                return $this->addField($input);
            case 'ninjaforms/update-field':
                return $this->updateField($input);
            case 'ninjaforms/remove-field':
                return $this->removeField($input);
            case 'ninjaforms/reorder-fields':
                return $this->reorderFields($input);
            case 'ninjaforms/update-form':
                return $this->updateForm($input);
            case 'ninjaforms/list-actions':
                return $this->listActions();
            case 'ninjaforms/add-action':
                return $this->addAction($input);
            case 'ninjaforms/update-action':
                return $this->updateAction($input);
            case 'ninjaforms/delete-action':
                return $this->deleteAction($input);
            case 'ninjaforms/list-calculations':
                return $this->listCalculations();
            case 'ninjaforms/add-calculation':
                return $this->addCalculation($input);
            case 'ninjaforms/update-calculation':
                return $this->updateCalculation($input);
            case 'ninjaforms/delete-calculation':
                return $this->deleteCalculation($input);
        }

        return new \WP_Error(
            'ability_not_allowed',
            __('That form operation is not available in chat.', 'ninja-forms')
        );
    }

    /**
     * Whether any successful operation changed the draft.
     *
     * @return bool
     */
    public function hasChanged(): bool
    {
        return $this->changed;
    }

    /**
     * Return the complete browser-facing draft.
     *
     * @return array
     */
    public function getDraft(): array
    {
        return $this->draft;
    }

    /**
     * Normalize the three browser collections without changing row settings.
     *
     * @param array $draft Browser form snapshot.
     * @return array
     */
    private function normalizeDraft(array $draft): array
    {
        return array(
            'fields' => isset($draft['fields']) && is_array($draft['fields'])
                ? array_values($draft['fields'])
                : array(),
            'actions' => isset($draft['actions']) && is_array($draft['actions'])
                ? array_values($draft['actions'])
                : array(),
            'settings' => isset($draft['settings']) && is_array($draft['settings'])
                ? $draft['settings']
                : array(),
        );
    }

    /**
     * Return a provider-safe projection of the current draft.
     *
     * @param array $input Ability arguments.
     * @return array
     */
    private function getForm(array $input): array
    {
        $settings = ninja_forms_ability_project_form_settings($this->draft['settings']);
        $form = array(
            'id' => $this->formId,
            'title' => isset($settings['title']) ? $settings['title'] : '',
            'settings' => $settings,
        );

        if (! isset($input['include_fields']) || $input['include_fields']) {
            $form['fields'] = array_map(
                static function (array $field): array {
                    $projected = ninja_forms_ability_project_field($field);
                    return array(
                        'id' => isset($projected['id']) ? $projected['id'] : 0,
                        'type' => isset($projected['type']) ? $projected['type'] : '',
                        'label' => isset($projected['label']) ? $projected['label'] : '',
                        'key' => isset($projected['key']) ? $projected['key'] : '',
                        'order' => isset($projected['order']) ? $projected['order'] : 0,
                        'required' => isset($projected['required']) ? $projected['required'] : 0,
                        'settings' => $projected,
                    );
                },
                $this->fieldsForRead()
            );
            $form['field_count'] = count($form['fields']);
        }

        if (! isset($input['include_actions']) || $input['include_actions']) {
            $form['actions'] = array_map(
                'ninja_forms_ability_project_action',
                $this->draft['actions']
            );
            $form['action_count'] = count($form['actions']);
        }

        if (! isset($input['include_calculations']) || $input['include_calculations']) {
            $calculations = isset($this->draft['settings']['calculations'])
                && is_array($this->draft['settings']['calculations'])
                ? $this->draft['settings']['calculations']
                : array();
            $form['calculations'] = ninja_forms_ability_project_calculations($calculations);
            $form['calculation_count'] = count($form['calculations']);
        }

        return array(
            'success' => true,
            'form' => $form,
            'message' => __('Retrieved the current form draft.', 'ninja-forms'),
        );
    }

    /**
     * Add a normalized field at a one-based insertion position.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function addField(array $input)
    {
        $input = ninja_forms_ability_normalize_field_input($input, 'add');
        if (is_wp_error($input)) {
            return $input;
        }
        if (empty($input['type'])) {
            return new \WP_Error('invalid_input', __('Field type is required.', 'ninja-forms'));
        }

        $type = $input['type'];
        $label = ! empty($input['label']) ? $input['label'] : ucfirst($type);
        $key = ! empty($input['key']) ? $input['key'] : sanitize_key($label);
        $key = str_replace('-', '_', $key);
        if (function_exists('ninja_forms_ensure_field_key_timestamp')) {
            $key = ninja_forms_ensure_field_key_timestamp($key);
        }
        $key = $this->uniqueFieldKey($key);

        $this->normalizeFieldOrders();
        $fieldTypes = array_map(
            static function (array $field): string {
                return isset($field['type']) ? (string) $field['type'] : '';
            },
            $this->draft['fields']
        );
        $requestedOrder = isset($input['order']) ? (int) $input['order'] : null;
        $order = ninja_forms_ability_resolve_field_insertion_order(
            $fieldTypes,
            $requestedOrder
        );

        $field = array(
            'id' => $this->allocateId(),
            '_ai_new' => true,
            'parent_id' => $this->formId,
            'type' => $type,
            'label' => $label,
            'key' => $key,
            'required' => isset($input['required']) ? $input['required'] : 0,
            'placeholder' => isset($input['placeholder']) ? $input['placeholder'] : '',
            'help_text' => isset($input['help_text']) ? $input['help_text'] : '',
            'admin_label' => isset($input['admin_label']) ? $input['admin_label'] : '',
            'label_pos' => isset($input['label_pos']) ? $input['label_pos'] : 'above',
            'order' => $order,
            'value' => '',
            'default' => '',
            'objectType' => 'Field',
            'objectDomain' => 'fields',
            'editActive' => false,
            'idAttribute' => 'id',
        );
        foreach (
            array(
                'personally_identifiable',
                'number_of_stars',
                'wrapper_class',
                'element_class',
                'container_class',
                'desc_text',
                'options',
            ) as $keyName
        ) {
            if (array_key_exists($keyName, $input)) {
                $field[$keyName] = $input[$keyName];
            }
        }
        if (array_key_exists('default', $input)) {
            $field['default'] = $input['default'];
        } elseif (array_key_exists('default_value', $input)) {
            $field['default'] = $input['default_value'];
        }
        if ('html' === $type) {
            $field['value'] = $field['default'];
        }

        array_splice($this->draft['fields'], $order - 1, 0, array($field));
        $this->normalizeFieldOrders();
        $this->changed = true;

        return array(
            'success' => true,
            'field_id' => $field['id'],
            'form_id' => $this->formId,
            'type' => $type,
            'order' => $order,
        );
    }

    /**
     * Update one field while preserving its stable ID across reordering.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function updateField(array $input)
    {
        $index = $this->findIndex(
            'fields',
            isset($input['field_id']) ? $input['field_id'] : null
        );
        if (false === $index) {
            return new \WP_Error(
                'field_not_found',
                __('That field is not in the current draft.', 'ninja-forms')
            );
        }

        $fieldId = $this->draft['fields'][$index]['id'];
        $fieldType = isset($this->draft['fields'][$index]['type'])
            ? (string) $this->draft['fields'][$index]['type']
            : '';
        $input = ninja_forms_ability_normalize_field_input($input, 'update', $fieldType);
        if (is_wp_error($input)) {
            return $input;
        }

        $map = array(
            'label' => 'label',
            'key' => 'key',
            'required' => 'required',
            'placeholder' => 'placeholder',
            'default_value' => 'default',
            'help_text' => 'help_text',
            'admin_label' => 'admin_label',
            'options' => 'options',
        );
        $updated = 0;
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $input)) {
                $this->draft['fields'][$index][$to] = $input[$from];
                if ('default' === $to && 'html' === $fieldType) {
                    $this->draft['fields'][$index]['value'] = $input[$from];
                }
                $updated++;
            }
        }
        if (array_key_exists('order', $input)) {
            $this->moveFieldToOrder($index, $input['order']);
            $updated++;
        }
        if ($updated > 0) {
            $this->changed = true;
        }

        return array('success' => true, 'field_id' => $fieldId, 'updated' => $updated);
    }

    /**
     * Remove one field.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function removeField(array $input)
    {
        $index = $this->findIndex(
            'fields',
            isset($input['field_id']) ? $input['field_id'] : null
        );
        if (false === $index) {
            return new \WP_Error(
                'field_not_found',
                __('That field is not in the current draft.', 'ninja-forms')
            );
        }
        $field = $this->draft['fields'][$index];
        array_splice($this->draft['fields'], $index, 1);
        $this->normalizeFieldOrders();
        $this->changed = true;

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'field_id' => $field['id'],
            'submissions_affected' => 0,
        );
    }

    /**
     * Reorder fields from an ID-to-order map.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function reorderFields(array $input)
    {
        if (empty($input['field_order']) || ! is_array($input['field_order'])) {
            return new \WP_Error('invalid_input', __('Field order is required.', 'ninja-forms'));
        }
        $this->normalizeFieldOrders();
        $updated = 0;
        foreach ($input['field_order'] as $id => $order) {
            $index = $this->findIndex('fields', $id);
            if (false !== $index) {
                $this->draft['fields'][$index]['order'] = (int) $order;
                $updated++;
            }
        }
        if ($updated > 0) {
            $this->sortFieldsByOrder();
            $this->normalizeFieldOrders();
            $this->changed = true;
        }

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'reordered' => $updated,
            'field_order' => $input['field_order'],
        );
    }

    /**
     * Update normalized form settings.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function updateForm(array $input)
    {
        $input = ninja_forms_ability_normalize_form_input($input);
        if (is_wp_error($input)) {
            return $input;
        }
        unset($input['form_id']);
        if (empty($input)) {
            return new \WP_Error('no_settings', __('No settings were provided.', 'ninja-forms'));
        }
        foreach ($input as $key => $value) {
            $this->draft['settings'][$key] = $value;
        }
        $this->changed = true;

        return array('success' => true, 'form_id' => $this->formId, 'updated' => $input);
    }

    /**
     * List provider-safe action projections.
     *
     * @return array
     */
    private function listActions(): array
    {
        $actions = array_map(
            'ninja_forms_ability_project_action',
            $this->draft['actions']
        );

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'count' => count($actions),
            'actions' => $actions,
        );
    }

    /**
     * Add one normalized action.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function addAction(array $input)
    {
        $input = ninja_forms_ability_normalize_action_input($input, '', 'add');
        if (is_wp_error($input)) {
            return $input;
        }
        $type = $input['type'];
        $action = array(
            'id' => $this->allocateId(),
            '_ai_new' => true,
            'parent_id' => $this->formId,
            'type' => $type,
            'label' => ! empty($input['label']) ? $input['label'] : ucfirst($type),
            'active' => isset($input['active']) ? $input['active'] : 1,
        );
        $this->applyActionSettings($action, $input);
        $this->draft['actions'][] = $action;
        $this->changed = true;

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'action_id' => $action['id'],
            'type' => $type,
        );
    }

    /**
     * Update one normalized action.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function updateAction(array $input)
    {
        $index = $this->findIndex(
            'actions',
            isset($input['action_id']) ? $input['action_id'] : null
        );
        if (false === $index) {
            return new \WP_Error(
                'action_not_found',
                __('That action is not in the current draft.', 'ninja-forms')
            );
        }
        $type = isset($this->draft['actions'][$index]['type'])
            ? (string) $this->draft['actions'][$index]['type']
            : '';
        $input = ninja_forms_ability_normalize_action_input($input, $type, 'update');
        if (is_wp_error($input)) {
            return $input;
        }
        if (isset($input['label'])) {
            $this->draft['actions'][$index]['label'] = $input['label'];
        }
        if (isset($input['active'])) {
            $this->draft['actions'][$index]['active'] = $input['active'];
        }
        $updated = $this->applyActionSettings($this->draft['actions'][$index], $input);
        if (isset($input['label'])) {
            $updated++;
        }
        if (isset($input['active'])) {
            $updated++;
        }
        if ($updated > 0) {
            $this->changed = true;
        }

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'action_id' => $this->draft['actions'][$index]['id'],
            'updated' => $updated,
        );
    }

    /**
     * Delete one action.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function deleteAction(array $input)
    {
        $index = $this->findIndex(
            'actions',
            isset($input['action_id']) ? $input['action_id'] : null
        );
        if (false === $index) {
            return new \WP_Error(
                'action_not_found',
                __('That action is not in the current draft.', 'ninja-forms')
            );
        }
        $action = $this->draft['actions'][$index];
        array_splice($this->draft['actions'], $index, 1);
        $this->changed = true;

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'action_id' => $action['id'],
        );
    }

    /**
     * List the draft's calculations.
     *
     * @return array
     */
    private function listCalculations(): array
    {
        $calculations = ninja_forms_ability_project_calculations($this->calculations());

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'count' => count($calculations),
            'calculations' => $calculations,
        );
    }

    /**
     * Add one calculation to the draft.
     *
     * Mirrors ninja_forms_ability_add_calculation, including its formula
     * validation, but writes to the draft instead of the stored form so the
     * change stays inside the same verify-and-roll-back boundary as every
     * other chat edit.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function addCalculation(array $input)
    {
        $name = isset($input['name']) ? sanitize_text_field((string) $input['name']) : '';
        if ('' === $name) {
            return new \WP_Error(
                'required_field',
                __('A calculation name is required.', 'ninja-forms')
            );
        }
        $formula = $this->validateFormula(isset($input['formula']) ? $input['formula'] : '');
        if (is_wp_error($formula)) {
            return $formula;
        }
        if (false !== $this->findCalculationIndex($name)) {
            return new \WP_Error(
                'calculation_exists',
                sprintf(
                    /* translators: %s: calculation name. */
                    __('A calculation named "%s" is already on this form.', 'ninja-forms'),
                    $name
                )
            );
        }

        $calculations = $this->calculations();
        $calculations[] = array(
            'name'          => $name,
            'eq'            => $formula,
            'dec'           => isset($input['decimal_places']) ? (int) $input['decimal_places'] : 2,
            'dec_point'     => isset($input['decimal_point'])
                ? sanitize_text_field((string) $input['decimal_point'])
                : '.',
            'thousands_sep' => isset($input['thousands_sep'])
                ? sanitize_text_field((string) $input['thousands_sep'])
                : ',',
        );
        $this->setCalculations($calculations);

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'name' => $name,
        );
    }

    /**
     * Update one of the draft's calculations.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function updateCalculation(array $input)
    {
        $name = isset($input['name']) ? sanitize_text_field((string) $input['name']) : '';
        $index = $this->findCalculationIndex($name);
        if (false === $index) {
            return new \WP_Error(
                'calculation_not_found',
                __('That calculation is not in the current draft.', 'ninja-forms')
            );
        }

        $calculations = $this->calculations();
        $updated = 0;
        if (isset($input['formula'])) {
            $formula = $this->validateFormula($input['formula']);
            if (is_wp_error($formula)) {
                return $formula;
            }
            $calculations[$index]['eq'] = $formula;
            $updated++;
        }
        $map = array(
            'decimal_places' => 'dec',
            'decimal_point' => 'dec_point',
            'thousands_sep' => 'thousands_sep',
        );
        foreach ($map as $from => $to) {
            if (! isset($input[$from])) {
                continue;
            }
            $calculations[$index][$to] = 'dec' === $to
                ? (int) $input[$from]
                : sanitize_text_field((string) $input[$from]);
            $updated++;
        }

        if ($updated > 0) {
            $this->setCalculations($calculations);
        }

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'name' => $name,
            'updated' => $updated,
        );
    }

    /**
     * Delete one of the draft's calculations.
     *
     * @param array $input Ability arguments.
     * @return array|\WP_Error
     */
    private function deleteCalculation(array $input)
    {
        $name = isset($input['name']) ? sanitize_text_field((string) $input['name']) : '';
        $index = $this->findCalculationIndex($name);
        if (false === $index) {
            return new \WP_Error(
                'calculation_not_found',
                __('That calculation is not in the current draft.', 'ninja-forms')
            );
        }

        $calculations = $this->calculations();
        array_splice($calculations, $index, 1);
        $this->setCalculations($calculations);

        return array(
            'success' => true,
            'form_id' => $this->formId,
            'name' => $name,
        );
    }

    /**
     * Validate a calculation formula through the shared ability validator.
     *
     * @param mixed $formula Provider-supplied formula.
     * @return string|\WP_Error Sanitized formula, or the validator's error.
     */
    private function validateFormula($formula)
    {
        if (! class_exists('NF_Abilities_Validation')) {
            return new \WP_Error(
                'unavailable',
                __('Calculations are unavailable.', 'ninja-forms')
            );
        }

        return \NF_Abilities_Validation::validate_calculation_formula($formula);
    }

    /**
     * Read the draft's calculation rows.
     *
     * @return array
     */
    private function calculations(): array
    {
        return isset($this->draft['settings']['calculations'])
            && is_array($this->draft['settings']['calculations'])
            ? array_values($this->draft['settings']['calculations'])
            : array();
    }

    /**
     * Replace the draft's calculation rows and mark the draft changed.
     *
     * @param array $calculations Complete calculation rows.
     * @return void
     */
    private function setCalculations(array $calculations): void
    {
        $this->draft['settings']['calculations'] = array_values($calculations);
        $this->changed = true;
    }

    /**
     * Find a calculation row index by name.
     *
     * @param string $name Calculation name.
     * @return int|false
     */
    private function findCalculationIndex(string $name)
    {
        if ('' === $name) {
            return false;
        }
        foreach ($this->calculations() as $index => $calculation) {
            if (isset($calculation['name']) && (string) $calculation['name'] === $name) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Copy normalized type-specific settings onto an action row.
     *
     * @param array $action Action row, passed by reference.
     * @param array $input  Normalized ability input.
     * @return int Number of changed settings.
     */
    private function applyActionSettings(array &$action, array $input): int
    {
        $map = array(
            'to' => 'to',
            'subject' => 'email_subject',
            'message' => 'email_message',
            'email_format' => 'email_format',
            'from_name' => 'from_name',
            'from_address' => 'from_address',
            'reply_to' => 'reply_to',
            'cc' => 'cc',
            'bcc' => 'bcc',
            'redirect_url' => 'redirect_url',
            'success_msg' => 'success_msg',
        );
        $updated = 0;
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $input)) {
                $action[$to] = $input[$from];
                $updated++;
            }
        }

        return $updated;
    }

    /**
     * Find a row index by stable ID.
     *
     * @param string $collection Draft collection name.
     * @param mixed  $id         Row ID.
     * @return int|false
     */
    private function findIndex(string $collection, $id)
    {
        foreach ($this->draft[$collection] as $index => $row) {
            if (isset($row['id']) && (string) $row['id'] === (string) $id) {
                return $index;
            }
        }

        return false;
    }

    /**
     * Allocate a temporary negative row ID.
     *
     * @return int
     */
    private function allocateId(): int
    {
        return $this->nextId--;
    }

    /**
     * Return fields with deterministic one-based order values.
     *
     * @return array
     */
    private function fieldsForRead(): array
    {
        $fields = $this->draft['fields'];
        foreach ($fields as $index => &$field) {
            $field['order'] = $index + 1;
        }
        unset($field);

        return $fields;
    }

    /**
     * Normalize collection order to contiguous one-based values.
     *
     * @return void
     */
    private function normalizeFieldOrders(): void
    {
        foreach ($this->draft['fields'] as $index => &$field) {
            $field['order'] = $index + 1;
        }
        unset($field);
    }

    /**
     * Move one field to a clamped one-based insertion position.
     *
     * @param int $index Current field index.
     * @param int $order Requested one-based order.
     * @return void
     */
    private function moveFieldToOrder(int $index, int $order): void
    {
        $this->normalizeFieldOrders();
        $field = $this->draft['fields'][$index];
        array_splice($this->draft['fields'], $index, 1);
        $order = max(1, min($order, count($this->draft['fields']) + 1));
        array_splice($this->draft['fields'], $order - 1, 0, array($field));
        $this->normalizeFieldOrders();
    }

    /**
     * Stable-sort fields by explicit order.
     *
     * @return void
     */
    private function sortFieldsByOrder(): void
    {
        $decorated = array();
        foreach ($this->draft['fields'] as $index => $field) {
            $decorated[] = array(
                'index' => $index,
                'order' => isset($field['order']) ? (int) $field['order'] : $index + 1,
                'field' => $field,
            );
        }
        usort(
            $decorated,
            static function (array $left, array $right): int {
                return $left['order'] === $right['order']
                    ? $left['index'] - $right['index']
                    : $left['order'] - $right['order'];
            }
        );
        $this->draft['fields'] = array_map(
            static function (array $row): array {
                return $row['field'];
            },
            $decorated
        );
    }

    /**
     * Make a generated field key unique within the browser draft.
     *
     * @param string $key Candidate field key.
     * @return string
     */
    private function uniqueFieldKey(string $key): string
    {
        $keys = array();
        foreach ($this->draft['fields'] as $field) {
            if (isset($field['key'])) {
                $keys[] = $field['key'];
            }
        }
        $candidate = $key;
        $suffix = 2;
        while (in_array($candidate, $keys, true)) {
            $base = preg_replace('/_\d{13}$/', '', $key);
            $candidate = $base . '_' . $suffix++ . '_' . round(microtime(true) * 1000);
        }

        return $candidate;
    }
}
