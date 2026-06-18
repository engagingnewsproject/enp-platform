<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Product;

use AC\Expression\StringComparisonSpecification;
use AC\FormatterCollection;
use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\Children;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;
use AC\Setting\Control\OptionCollection;
use AC\Setting\Control\Type\Option;
use ACA\WC\Value\Formatter\Product\SecondaryProductValue;

class SecondaryProductProperty extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Secondary Info', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_select(
            'secondary_product_property',
            $this->get_options(),
            $config->get('secondary_product_property', ''),
            null,
            null,
            new AttributeCollection([AttributeFactory::create_refresh()])
        );
    }

    private function get_options(): OptionCollection
    {
        return new OptionCollection([
            new Option(__('None', 'codepress-admin-columns'), ''),
            new Option(__('SKU', 'woocommerce'), 'sku'),
            new Option(__('Price', 'woocommerce'), 'price'),
            new Option(__('Stock status', 'woocommerce'), 'stock_status'),
            new Option(__('Taxonomy', 'codepress-admin-columns'), 'taxonomy'),
            new Option(__('Quantity', 'woocommerce'), 'quantity'),
        ]);
    }

    protected function get_children(Config $config): ?Children
    {
        $property = (string)$config->get('secondary_product_property', '');

        if ('taxonomy' !== $property) {
            return null;
        }

        $taxonomy_component = (new ProductTaxonomy())->create(
            $config,
            StringComparisonSpecification::equal('taxonomy')
        );

        return new Children(new ComponentCollection([$taxonomy_component]));
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        $property = (string)$config->get('secondary_product_property', '');

        if ('' === $property) {
            return;
        }

        $taxonomy = (string)$config->get('taxonomy', '');

        $formatters->add(new SecondaryProductValue($property, $taxonomy));
    }

}
