<?php

declare(strict_types=1);

namespace ACA\WC\Helper;

use AC\Helper\Creatable;

final class Attributes extends Creatable
{

    public function get_raw_attributes(): array
    {
        global $wpdb;

        $results = wp_cache_get('attributes', 'product_attribute');

        if (false === $results) {
            $results = $wpdb->get_col(
                "
				SELECT {$wpdb->postmeta}.meta_value 
				FROM {$wpdb->postmeta} 
				WHERE meta_key = '_product_attributes'
			"
            );

            wp_cache_add('attributes', $results, 'product_attribute');
        }

        if ( ! $results) {
            return [];
        }

        return array_filter(array_map('unserialize', $results));
    }

    public function get_custom_attributes(): array
    {
        $attributes = [];

        foreach ($this->get_raw_attributes() as $atts) {
            foreach ($atts as $key => $attr) {
                if (empty($attr['is_taxonomy'])) {
                    $attributes[$key] = $attr;
                }
            }
        }

        return $attributes;
    }

}