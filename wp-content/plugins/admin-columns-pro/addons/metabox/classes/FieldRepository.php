<?php

declare(strict_types=1);

namespace ACA\MetaBox;

use AC;
use AC\TableScreen;
use ACA\MetaBox\Field\Field;
use MB_Comment_Meta_Box;

final class FieldRepository
{

    private FieldFactory $field_factory;

    public function __construct(FieldFactory $field_factory)
    {
        $this->field_factory = $field_factory;
    }

    public function find(string $field_key, TableScreen $table_screen): ?Field
    {
        $fields = $this->get_field_configs_by_table_screen($table_screen);

        $settings = $fields[$field_key] ?? null;

        return $settings
            ? $this->field_factory->create($settings)
            : null;
    }

    /**
     * @return Field[]
     */
    public function find_all(TableScreen $table_screen): array
    {
        $fields = [];
        foreach ($this->get_field_configs_by_table_screen($table_screen) as $settings) {
            $field = $this->field_factory->create($settings);

            if ($field) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    private function get_field_configs_by_table_screen(TableScreen $table_screen): array
    {
        if ( ! function_exists('rwmb_get_object_fields')) {
            return [];
        }

        switch (true) {
            case $table_screen instanceof AC\PostType:
                return rwmb_get_object_fields((string)$table_screen->get_post_type());
            case $table_screen instanceof AC\TableScreen\User:
                return rwmb_get_object_fields('user', 'user');
            case $table_screen instanceof AC\Taxonomy:
                return rwmb_get_object_fields((string)$table_screen->get_taxonomy(), 'term');
            case $table_screen instanceof AC\TableScreen\Comment:
                return $this->get_comment_fields();
            default:
                return [];
        }
    }

    private function get_comment_fields(): array
    {
        if ( ! class_exists('MB_Comment_Meta_Box', false)) {
            return [];
        }

        $fields = [];
        $metaboxes = rwmb_get_registry('meta_box')->get_by(['object_type' => 'comment']);

        foreach ($metaboxes as $metabox) {
            if ( ! $metabox instanceof MB_Comment_Meta_Box) {
                continue;
            }

            foreach ($metabox->fields as $field) {
                $fields[$field['id']] = $field;
            }
        }

        return $fields;
    }

}