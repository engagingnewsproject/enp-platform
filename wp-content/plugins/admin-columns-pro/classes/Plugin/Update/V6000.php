<?php

declare(strict_types=1);

namespace ACP\Plugin\Update;

use AC\Plugin\Update;
use AC\Plugin\Version;

class V6000 extends Update
{

    public function __construct()
    {
        parent::__construct(new Version('6.0'));
    }

    public function apply_update(): void
    {
        $this->apply_acf_update();
    }

    /**
     * Each ACF field is listed as a separate column. Previously there would be a single
     * ACF column with an options to choose its field type.
     */
    private function apply_acf_update(): void
    {
        if ( ! function_exists('acf_get_field')) {
            return;
        }

        global $wpdb;

        $results = $wpdb->get_results("SELECT id, list_id, columns FROM {$wpdb->prefix}admin_columns");

        if ( ! $results) {
            return;
        }

        $updates = [];

        foreach ($results as $view) {
            if ( ! $view->columns) {
                continue;
            }

            $has_changed_columns = false;
            $columns = unserialize($view->columns, ['allowed_classes' => false]);
            $columns = array_values($columns);

            foreach ($columns as $i => $column) {
                if ($column['type'] !== 'column-acf_field') {
                    continue;
                }

                $field_type = $column['field'];

                $field = acf_get_field($field_type) ?: null;

                // ACF field group
                if ($field && $field['type'] === 'group' && isset($options['sub_field'])) {
                    $field_type = 'acfgroup__' . $field_type . '-' . $options['sub_field'];
                }

                $column[$i]['type'] = $field_type;
                $has_changed_columns = true;
            }

            if ($has_changed_columns) {
                $updates[$view->id] = serialize($columns);
            }
        }

        foreach ($updates as $id => $columns) {
            $wpdb->query(
                $wpdb->prepare("UPDATE {$wpdb->prefix}admin_columns SET columns = %s WHERE ID = %d", $columns, $id)
            );
        }
    }

}