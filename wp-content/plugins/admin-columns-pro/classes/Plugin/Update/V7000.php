<?php

declare(strict_types=1);

namespace ACP\Plugin\Update;

use AC\Plugin\Update;
use AC\Plugin\Version;
use AC\TableScreenFactory\Aggregate;
use AC\Type\ListScreenId;
use AC\Type\TableId;
use ACA;
use ACP;
use ACP\ConditionalFormat\Storage\Table\ConditionalFormat;
use ACP\ConditionalFormat\Type\KeyGenerator;
use ACP\Plugin\Update\V7000\FileRepository;
use DateTime;
use Exception;
use RuntimeException;

final class V7000 extends Update
{

    private FileRepository $file_repository;

    private ACA\MetaBox\FieldRepository $meta_box_repository;

    private Aggregate $table_screen_factory;

    private ACA\JetEngine\FieldRepository $jet_engine_field_repository;

    public function __construct(
        FileRepository $file_repository,
        ACA\MetaBox\FieldRepository $meta_box_repository,
        ACA\JetEngine\FieldRepository $jet_engine_field_repository,
        Aggregate $table_screen_factory
    ) {
        parent::__construct(new Version('7.0'));

        $this->file_repository = $file_repository;
        $this->meta_box_repository = $meta_box_repository;
        $this->table_screen_factory = $table_screen_factory;
        $this->jet_engine_field_repository = $jet_engine_field_repository;
    }

    public function apply_update(): void
    {
        $this->create_database_conditional_formatting();
        $this->update_database_table_definitions();
        $this->update_database_columns();
        $this->update_file_storage();
        $this->delete_filtering_cache();
    }

    private function create_database_conditional_formatting(): void
    {
        $table = new ConditionalFormat();

        if ( ! $table->exists()) {
            $table->create();
        }

        global $wpdb;

        $sql = "
			SELECT user_id, meta_key, meta_value
			FROM $wpdb->usermeta
			WHERE `meta_key` LIKE 'ac\_conditional\_format\_%'
		";

        $results = $wpdb->get_results($sql);

        if ( ! is_array($results)) {
            return;
        }

        $key_generator = new KeyGenerator();

        foreach ($results as $row) {
            $list_id = str_replace('ac_conditional_format_', '', $row->meta_key);

            if ( ! ListScreenId::is_valid_id($list_id)) {
                continue;
            }

            $values = unserialize($row->meta_value, ['allowed_classes' => ['ACP\ConditionalFormat\Type\Format']]);

            if ( ! $values) {
                continue;
            }

            $rules = [];

            foreach ($values as $value) {
                $format_type = $value['format'] ?? null;

                if ($format_type && ! $format_type instanceof ACP\ConditionalFormat\Type\Format) {
                    throw new RuntimeException('Missing class: ACP\ConditionalFormat\Type\Format');
                }

                $rules[] = [
                    'column_name' => $value['column_name'],
                    'format'      => (string)$format_type,
                    'operator'    => $value['operator'] ?? '',
                    'fact'        => $value['fact'] ?? null,
                ];
            }

            try {
                $wpdb->insert(
                    $wpdb->prefix . 'ac_conditional_format',
                    [
                        'rules_key' => (string)$key_generator->generate(),
                        'list_screen_id' => (string)$list_id,
                        'user_id' => (int)$row->user_id,
                        'data' => serialize($rules),
                        'date_modified' => (new DateTime())->format('Y-m-d H:i:s'),
                    ],
                    [
                        '%s',
                        '%s',
                        '%d',
                        '%s',
                        '%s',
                    ]
                );
            } catch (Exception $e) {
                continue;
            }
        }
    }

    private function update_database_columns(): void
    {
        global $wpdb;

        $results = $wpdb->get_results("SELECT id, list_id, list_key, columns FROM {$wpdb->prefix}admin_columns");

        foreach ($results as $row) {
            $columns = $row->columns
                ? unserialize($row->columns, ['allowed_classes' => false])
                : null;

            if ( ! $columns || ! is_array($columns)) {
                continue;
            }

            $columns = array_values($columns);

            $this->update_database_core_changes((int)$row->id, $columns);
            $this->update_database_metabox_changes((int)$row->id, new TableId($row->list_key), $columns);
            $this->update_database_jetengine_changes((int)$row->id, new TableId($row->list_key), $columns);
        }
    }

    private function update_database_table_definitions(): void
    {
        global $wpdb;

        $collate = $wpdb->collate ?: 'utf8mb4_unicode_ci';
        $segments_table = "{$wpdb->prefix}ac_segments";

        // Remove 'key' to a less ambiguous name and make sure our natural keys can evolve to UUID if need be
        $changes = [
            "ALTER TABLE {$segments_table} CHANGE COLUMN `key` `segment_key` VARCHAR(36) COLLATE {$collate} NOT NULL;",
            "ALTER TABLE {$segments_table} DROP INDEX `key`, ADD UNIQUE KEY `segment_key` (`segment_key`);",
            "ALTER TABLE {$segments_table} CHANGE COLUMN `list_screen_id` `list_screen_id` VARCHAR(36) COLLATE {$collate} NOT NULL DEFAULT '';",
        ];

        foreach ($changes as $sql) {
            $wpdb->query($sql);
        }
    }

    private function update_file_storage(): void
    {
        // re-fetch file data every update, because the previous update may have caused the file data to contains changes

        // Update versions 6.3 to 7.0
        foreach ($this->file_repository->find_all('6.3', '7.0') as $file_path => $data) {
            $this->update_file_core_changes_v63_to_v70($file_path, $data);
        }
        foreach ($this->file_repository->find_all('6.3', '7.0') as $file_path => $data) {
            $this->update_file_metabox_changes_v63_to_v70($file_path, $data);
        }
        foreach ($this->file_repository->find_all('6.3', '7.0') as $file_path => $data) {
            $this->update_file_jetengine_changes_v63_to_v70($file_path, $data);
        }

        // Update versions 5.1 to 6.3
        foreach ($this->file_repository->find_all('5.1', '6.3') as $file_path => $data) {
            $this->update_file_core_changes_v51_to_v63($file_path, $data);
        }
    }

    private function update_file_core_changes_v51_to_v63(string $file_path, array $data): void
    {
        if ( ! isset($data['columns'])) {
            return;
        }

        $updated = false;

        foreach ($data['columns'] as $column_name => $column_data) {
            if ( ! $column_data || ! is_array($column_data)) {
                continue;
            }

            $modified_data = $this->modify_column_options($column_data);

            if ($modified_data) {
                $data['columns'][$column_name] = $modified_data;
                $updated = true;
            }
        }

        foreach ($data['columns'] as $column_name => $column_data) {
            if ( ! $column_data || ! is_array($column_data)) {
                continue;
            }

            // Because the core plugin will not update the file storage, we need to apply
            // the core changes (see V5000) to these files
            $modified_data = $this->modify_column_options_for_core_plugin($column_data);

            if ($modified_data) {
                $data['columns'][$column_name] = $modified_data;
                $updated = true;
            }
        }

        if ($updated) {
            $this->file_repository->save($file_path, $data);
        }
    }

    private function update_file_jetengine_changes_v63_to_v70(string $file_path, array $data): void
    {
        $type = $data['list_screen']['type'] ?? '';

        if ( ! TableId::validate((string)$type)) {
            return;
        }

        $updated = false;

        $fields = $this->get_jetengine_field_names(new TableId($type));

        foreach ($data['list_screen']['columns'] as $column_name => $column_data) {
            $type = $column_data['type'] ?? null;

            if ( ! in_array($type, $fields, true)) {
                continue;
            }

            // Add a prefix to the column type
            $data['list_screen']['columns'][$column_name]['type'] = 'column-jetengine-' . $type;
            $updated = true;
        }

        if ($updated) {
            $this->file_repository->save($file_path, $data);
        }
    }

    private function update_file_metabox_changes_v63_to_v70(string $file_path, array $data): void
    {
        $type = $data['list_screen']['type'] ?? '';

        if ( ! TableId::validate((string)$type)) {
            return;
        }

        $updated = false;

        $fields = $this->get_metabox_field_ids(new TableId($type));

        foreach ($data['list_screen']['columns'] as $column_name => $column_data) {
            $type = $column_data['type'] ?? null;

            if ( ! in_array($type, $fields, true)) {
                continue;
            }

            // Add a metabox prefix to the column type
            $data['list_screen']['columns'][$column_name]['type'] = 'column-metabox-' . $type;
            $updated = true;
        }

        if ($updated) {
            $this->file_repository->save($file_path, $data);
        }
    }

    private function get_metabox_field_ids(TableId $table_id): array
    {
        if ( ! $this->table_screen_factory->can_create($table_id)) {
            return [];
        }

        try {
            $fields = $this->meta_box_repository->find_all($this->table_screen_factory->create($table_id));
        } catch (Exception $e) {
            return [];
        }

        $ids = [];

        foreach ($fields as $field) {
            $is_relation = 1 === (int)$field->get_setting('relationship', 0);

            if ($is_relation) {
                continue;
            }

            $ids[] = $field->get_id();
        }

        return $ids;
    }

    private function update_file_core_changes_v63_to_v70(string $file_path, array $data): void
    {
        if ( ! isset($data['list_screen']['columns'])) {
            return;
        }

        $updated = false;

        foreach ($data['list_screen']['columns'] as $column_name => $column_data) {
            if ( ! $column_data || ! is_array($column_data)) {
                continue;
            }

            $modified_data = $this->modify_column_options($column_data);

            if ($modified_data) {
                $data['list_screen']['columns'][$column_name] = $modified_data;
                $updated = true;
            }
        }

        foreach ($data['list_screen']['columns'] as $column_name => $column_data) {
            if ( ! $column_data || ! is_array($column_data)) {
                continue;
            }

            // Because the core plugin will not update the file storage, we need to apply
            // the core changes (see V5000) to these files
            $modified_data = $this->modify_column_options_for_core_plugin($column_data);

            if ($modified_data) {
                $data['list_screen']['columns'][$column_name] = $modified_data;
                $updated = true;
            }
        }

        if ($updated) {
            $this->file_repository->save($file_path, $data);
        }
    }

    private function update_database_metabox_changes(int $id, TableId $table_id, array $columns): void
    {
        $updated = false;

        $fields = $this->get_metabox_field_ids($table_id);

        foreach ($columns as $i => $column_data) {
            if ( ! $column_data || ! is_array($column_data)) {
                continue;
            }

            $type = $column_data['type'] ?? null;

            if ( ! in_array($type, $fields, true)) {
                continue;
            }

            // Add a metabox prefix to the column type
            $columns[$i]['type'] = 'column-metabox-' . $type;
            $updated = true;
        }

        if ($updated) {
            $this->update_database_record($id, $columns);
        }
    }

    private function update_database_jetengine_changes(int $id, TableId $table_id, array $columns): void
    {
        $updated = false;

        $fields = $this->get_jetengine_field_names($table_id);

        foreach ($columns as $i => $column_data) {
            if ( ! $column_data || ! is_array($column_data)) {
                continue;
            }

            $type = $column_data['type'] ?? null;

            if ( ! in_array($type, $fields, true)) {
                continue;
            }

            // Add a prefix to the column type
            $columns[$i]['type'] = 'column-jetengine-' . $type;
            $updated = true;
        }

        if ($updated) {
            $this->update_database_record($id, $columns);
        }
    }

    private function get_jetengine_field_names(TableId $table_id): array
    {
        if ( ! $this->table_screen_factory->can_create($table_id)) {
            return [];
        }

        try {
            $fields = $this->jet_engine_field_repository->find_all($this->table_screen_factory->create($table_id));
        } catch (Exception $e) {
            return [];
        }

        $ids = [];

        foreach ($fields as $field) {
            $ids[] = $field->get_name();
        }

        return $ids;
    }

    private function update_database_core_changes(int $id, array $columns): void
    {
        $updated = false;

        foreach ($columns as $i => $column) {
            if ( ! $column || ! is_array($column)) {
                continue;
            }

            $updated_column = $this->modify_column_options($column);

            if ($updated_column) {
                $columns[$i] = $updated_column;
                $updated = true;
            }
        }

        if ($updated) {
            $this->update_database_record($id, $columns);
        }
    }

    private function update_database_record(int $id, array $columns): void
    {
        global $wpdb;

        $sql = $wpdb->prepare(
            "UPDATE {$wpdb->prefix}admin_columns SET columns = %s WHERE id = %d",
            serialize($columns),
            $id
        );

        $wpdb->query($sql);
    }

    private function modify_column_options(array $column): ?array
    {
        if ( ! isset($column['type'])) {
            return null;
        }

        // Pods Migration
        if ($column['type'] === 'column-pods' && isset($column['pods_field'])) {
            $column['type'] = 'column-pod_' . $column['pods_field'];

            return $column;
        }

        // Types Migration
        if ($column['type'] === 'column-types') {
            $column['type'] = 'column-types_' . $column['types_field'];

            return $column;
        }

        return null;
    }

    /**
     * Copied from V5000 update
     * @see Update\V7000::modify_column_options()
     */
    private function modify_column_options_for_core_plugin(array $column): ?array
    {
        if ( ! isset($column['type'])) {
            return null;
        }

        if ($column['type'] === 'column-user_posts') {
            $column['type'] = 'column-user_postcount';

            return $column;
        }

        if ( ! empty($column['character_limit'])) {
            $column['excerpt_length'] = $column['character_limit'];
            unset($column['character_limit']);

            return $column;
        }

        if ($column['type'] === 'column-mediaid') {
            $column['type'] = 'column-postid';

            return $column;
        }

        return null;
    }

    private function delete_filtering_cache(): void
    {
        global $wpdb;

        // Delete filtering cache from the version 5.x and older
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'ac_cache_data%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE 'ac_cache_expires_%'");
    }

}