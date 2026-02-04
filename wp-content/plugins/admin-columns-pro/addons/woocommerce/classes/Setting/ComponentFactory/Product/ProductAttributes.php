<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Product;

use AC;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use ACA\WC\Helper\Attributes;

class ProductAttributes extends BaseComponentFactory
{

    public const NAME = 'product_taxonomy_display';

    protected function get_label(Config $config): ?string
    {
        return __('Show Single', 'codepress-admin-columns');
    }

    protected function get_description(Config $config): ?string
    {
        return __('Display a single attribute.', 'codepress-admin-columns') . ' ' . __(
                'Only works for taxonomy attributes.',
                'codepress-admin-columns'
            );
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            $this->get_attribute_options(),
            $config->get(self::NAME),
            null,
            false,
            new AC\Setting\AttributeCollection([
                AC\Setting\AttributeFactory::create_refresh(),
            ])
        );
    }

    private function get_taxonomy_attributes_options(): AC\Setting\Control\OptionCollection
    {
        global $wc_product_attributes;

        $attributes = new AC\Setting\Control\OptionCollection();

        foreach ($wc_product_attributes as $name => $taxonomy) {
            $attributes->add(
                new AC\Setting\Control\Type\Option(
                    (string)($taxonomy->attribute_label ?? ''),
                    (string)$name,
                    'Taxonomies'
                )
            );
        }

        return $attributes;
    }

    private function get_custom_attribute_options(): AC\Setting\Control\OptionCollection
    {
        $attributes = new AC\Setting\Control\OptionCollection();
        $raw_attributes = (new Attributes())->get_raw_attributes();

        foreach ($raw_attributes as $atts) {
            foreach ($atts as $key => $attr) {
                $is_taxonomy = $attr['is_taxonomy'] ?? false;

                if ($is_taxonomy) {
                    continue;
                }

                $name = $attr['name'] ?? null;

                if ( ! $name) {
                    continue;
                }

                $attributes->add(
                    new AC\Setting\Control\Type\Option(
                        (string)$name,
                        (string)$key,
                        'Custom'
                    )
                );
            }
        }

        return $attributes;
    }

    private function get_attribute_options(): AC\Setting\Control\OptionCollection
    {
        $options = new AC\Setting\Control\OptionCollection();

        $options->add(new AC\Setting\Control\Type\Option(__('All attributes', 'codepress-admin-columns'), ''));

        foreach ($this->get_taxonomy_attributes_options() as $option) {
            $options->add($option);
        }

        foreach ($this->get_custom_attribute_options() as $option) {
            $options->add($option);
        }

        return $options;
    }

}