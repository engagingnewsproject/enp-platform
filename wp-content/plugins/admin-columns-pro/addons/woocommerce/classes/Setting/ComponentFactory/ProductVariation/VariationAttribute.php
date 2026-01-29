<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\ProductVariation;

use AC;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use ACA\WC\Type\ProductAttribute;

class VariationAttribute extends BaseComponentFactory
{

    public const NAME = 'variation_attribute';

    protected function get_label(Config $config): ?string
    {
        return __('Attribute', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            $this->get_input_options(),
            $config->get(self::NAME, ''),
            __('Select attribute', 'codepress-admin-columns')

        );
    }

    private function get_attributes_meta_keys()
    {
        global $wpdb;

        $cache_key = 'ac_setting_attribute_variation_display';
        $attributes = wp_cache_get('attributes', $cache_key);

        if (false === $attributes) {
            $attributes = $wpdb->get_col(
                "
				SELECT DISTINCT pm.meta_key 
				FROM {$wpdb->postmeta} AS pm
				INNER JOIN {$wpdb->posts} AS pp ON pp.ID = pm.post_id
				WHERE pp.post_type = 'product_variation' AND pm.meta_key LIKE 'attribute_%'
			"
            );

            wp_cache_add('attributes', $attributes, $cache_key);
        }

        if ( ! $attributes) {
            return [];
        }

        return $attributes;
    }

    private function remove_attribute_prefix($meta_key)
    {
        return preg_replace("/^attribute_/", '', $meta_key);
    }

    protected function get_input_options(): OptionCollection
    {
        $options = new OptionCollection();

        foreach ($this->get_attributes_meta_keys() as $meta_key) {
            $attribute = new ProductAttribute($this->remove_attribute_prefix($meta_key));

            $options->add(
                new AC\Setting\Control\Type\Option(
                    $attribute->get_label(),
                    $attribute->get_name(),
                    $attribute->is_taxonomy()
                        ? __('Global Attributes', 'codepress-admin-columns')
                        : __(
                        'Product Attributes',
                        'codepress-admin-columns'
                    )
                )
            );
        }

        return $options;
    }

}