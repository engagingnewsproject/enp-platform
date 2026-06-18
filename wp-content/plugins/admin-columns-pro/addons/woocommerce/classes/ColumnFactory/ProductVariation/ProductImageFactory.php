<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\Formatter\Post\FeaturedImage;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\ImageSize;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class ProductImageFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private ImageSize $image_size;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ImageSize $image_size
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->image_size = $image_size;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->image_size->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-variation_thumb';
    }

    public function get_label(): string
    {
        return __('Product image', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new FeaturedImage());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Post\FeaturedImage();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\FeaturedImage('product');
    }

}