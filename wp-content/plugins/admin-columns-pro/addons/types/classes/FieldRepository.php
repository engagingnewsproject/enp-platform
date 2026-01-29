<?php

declare(strict_types=1);

namespace ACA\Types;

use AC;
use AC\TableScreen;
use ACP;
use Toolset_Field_Group_Term;

final class FieldRepository
{

    /**
     * @return Field[]
     */
    public function find_all(TableScreen $table_screen): array
    {
        $fields = [];
        switch (true) {
            case $table_screen instanceof AC\TableScreen\Post:
                $fields = $this->get_post_type_fields($table_screen);
                break;
            case $table_screen instanceof AC\TableScreen\User:
                $fields = $this->get_user_fields();
                break;
            case $table_screen instanceof ACP\TableScreen\Taxonomy:
                $fields = $this->get_taxonomy_fields($table_screen);
                break;
        }

        return array_map(static function ($field) {
            return new Field($field);
        }, $fields);
    }

    private function get_post_type_fields(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\PostType) {
            return [];
        }

        /** @noinspection PhpParamsInspection */
        $groups = wpcf_admin_get_groups_by_post_type((string)$table_screen->get_post_type());
        $group_fields = [];

        foreach ($groups as $group_id => $group) {
            $group_fields[] = wpcf_admin_fields_get_fields_by_group($group_id, 'slug', true, false);
        }

        return array_merge(...$group_fields);
    }

    private function get_user_fields(): array
    {
        $group_fields = [];

        $groups = get_posts([
            'fields'         => 'ids',
            'posts_per_page' => -1,
            'post_type'      => 'wp-types-user-group',
        ]);

        foreach ($groups as $group_id) {
            $group_fields[] = wpcf_admin_fields_get_fields_by_group(
                $group_id,
                'slug',
                true,
                false,
                true,
                TYPES_USER_META_FIELD_GROUP_CPT_NAME,
                'wpcf-usermeta'
            );
        }

        return array_merge(...$group_fields);
    }

    private function get_taxonomy_fields(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof AC\Taxonomy) {
            return [];
        }

        $group_fields = [];

        foreach ($this->get_taxonomy_groups($table_screen->get_taxonomy()) as $group_id) {
            $group_fields[] = wpcf_admin_fields_get_fields_by_group(
                $group_id,
                'slug',
                true,
                false,
                true,
                TYPES_TERM_META_FIELD_GROUP_CPT_NAME,
                'wpcf-termmeta'
            );
        }

        return array_merge(...$group_fields);
    }

    private function get_taxonomy_groups(AC\Type\TaxonomySlug $taxonomy): array
    {
        $posts = get_posts([
            'posts_per_page' => -1,
            'post_type'      => 'wp-types-term-group',
        ]);

        $groups = [];

        foreach ($posts as $post) {
            $group = new Toolset_Field_Group_Term($post);
            $taxonomies = $group->get_associated_taxonomies();

            if (empty($taxonomies) || in_array((string)$taxonomy, $taxonomies)) {
                $groups[] = $post->ID;
            }
        }

        return $groups;
    }

}