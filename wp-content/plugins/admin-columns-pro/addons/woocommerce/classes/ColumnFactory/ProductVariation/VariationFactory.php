<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\ProductVariation\VariationDisplay;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;

class VariationFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private VariationDisplay $variation_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        VariationDisplay $variation_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->variation_display = $variation_display;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->variation_display->create($config));
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_bulk_edit();
    }

    public function get_column_type(): string
    {
        return 'variation_attributes';
    }

    public function get_label(): string
    {
        return __('Variation', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        switch ($config->get('variation_display', '')) {
            case'short':
                return parent::get_formatters($config)->add(new Formatter\ProductVariation\VariationShort());
            default:
                return parent::get_formatters($config)->add(new Formatter\ProductVariation\VariationWithLabel());
        }
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductVariation\Variation();
    }

}