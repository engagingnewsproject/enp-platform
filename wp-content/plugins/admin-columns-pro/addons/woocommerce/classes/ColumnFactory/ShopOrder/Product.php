<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Product\ProductProperty;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class Product extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private ProductProperty $product_property;

    private ComponentFactory\PostLink $post_link;

    private ComponentFactory\NumberOfItems $number_of_items;

    private ComponentFactory\Separator $separator;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ProductProperty $product_property,
        ComponentFactory\PostLink $post_link,
        ComponentFactory\NumberOfItems $number_of_items,
        ComponentFactory\Separator $separator
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->product_property = $product_property;
        $this->post_link = $post_link;
        $this->number_of_items = $number_of_items;
        $this->separator = $separator;
    }

    public function get_column_type(): string
    {
        return 'column-wc-product';
    }

    public function get_label(): string
    {
        return __('Product', 'woocommerce');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->product_property->create($config))
                     ->add($this->post_link->create($config))
                     ->add($this->number_of_items->create($config))
                     ->add($this->separator->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\ShopOrder\Products())
                     ->add(
                         AC\Formatter\Collection\Separator::create_from_config($config)
                     );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopOrder\Product('shop_order');
    }

}