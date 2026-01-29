<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\ProductVariation\VariationAttribute;
use ACA\WC\Sorting;
use ACA\WC\Type\ProductAttribute;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class AttributeFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private VariationAttribute $variation_attribute;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        VariationAttribute $variation_attribute
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->variation_attribute = $variation_attribute;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->variation_attribute->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-variation_attribute';
    }

    public function get_label(): string
    {
        return __('Attribute', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        $attribute = $this->get_product_attribute($config);

        if ($attribute) {
            $formatters->add(
                new Formatter\ProductVariation\VariationAttribute($attribute)
            );
        }

        return $formatters;
    }

    private function get_product_attribute(Config $config): ?ProductAttribute
    {
        $attribute = $config->get(VariationAttribute::NAME, '');

        if ( ! $attribute) {
            return null;
        }

        return new ProductAttribute($attribute);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $attribute = $this->get_product_attribute($config);

        if ( ! $attribute) {
            return null;
        }

        if ($attribute->is_taxonomy()) {
            return new Search\ProductVariation\AttributeTaxonomy($attribute->get_name());
        }

        return new Search\ProductVariation\Attribute('attribute_' . $attribute->get_name());
    }

}