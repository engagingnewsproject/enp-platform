<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\NetworkSite;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use AC\Setting\Control\Type\Option;

class SiteOptions extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Option', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'field',
            $this->get_field_options(),
            $config->get('field', '')
        );
    }

    private function get_field_options(): OptionCollection
    {
        global $wpdb;

        $keys = [];

        if ( ! function_exists('get_sites')) {
            return new OptionCollection();
        }

        foreach (get_sites() as $site) {
            $table = $wpdb->get_blog_prefix($site->blog_id) . 'options';

            $sql = "
					SELECT option_name, option_value 
					FROM $table
					WHERE option_name NOT LIKE %s
				";

            // Exclude transients
            $values = $wpdb->get_results($wpdb->prepare($sql, $wpdb->esc_like('_transient') . '%'));

            // Exclude serialized data
            foreach ($values as $value) {
                if (is_serialized($value->option_value)) {
                    continue;
                }

                $keys[$value->option_name] = $value->option_name;
            }
        }

        natcasesort($keys);

        $options = new OptionCollection();

        foreach ($keys as $key) {
            $options->add(new Option((string)$key, (string)$key));
        }

        return $options;
    }

}