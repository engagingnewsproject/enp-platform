<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\NetworkSite;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use WP_Site;

class PostType extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Post Type', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'post_type',
            $this->get_options(),
            $config->get('post_type', '')
        );
    }

    private function get_options(): OptionCollection
    {
        $options = [];

        foreach ($this->get_post_types() as $post_type) {
            $post_type_object = get_post_type_object($post_type);

            $label = $post_type_object
                ? $post_type_object->labels->name
                : $post_type;

            $options[$post_type] = sprintf('%s (%s)', $label, $post_type);
        }

        natcasesort($options);

        $post_types = ['' => __('All post types', 'codepress-admin-columns')] + $options;

        return OptionCollection::from_array($post_types);
    }

    private function get_post_types(): array
    {
        return $this->get_cached_options();
    }

    private function get_cached_options()
    {
        $values = wp_cache_get('ac-site-settings', 'ac-site-post-types');

        if ( ! $values) {
            $values = $this->get_distinct_db_values();

            wp_cache_add('ac-site-settings', $values, 'ac-site-post-types', 60);
        }

        return $values;
    }

    private function get_distinct_db_values(): array
    {
        if ( ! function_exists('get_sites')) {
            return [];
        }

        global $wpdb;

        $queries = [];

        foreach (get_sites() as $site) {
            /* @var WP_Site $site */
            $table = $wpdb->get_blog_prefix($site->id) . 'posts';

            $sql = "SELECT DISTINCT post_type FROM $table";

            $queries[] = $sql;
        }

        return $wpdb->get_col(implode(" UNION ", $queries));
    }

}