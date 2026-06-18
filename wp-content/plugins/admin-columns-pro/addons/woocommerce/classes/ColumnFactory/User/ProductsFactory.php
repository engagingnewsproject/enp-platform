<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\User\UserProducts;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class ProductsFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    private UserProducts $user_products;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        UserProducts $user_products
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->user_products = $user_products;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->user_products->create($config));
    }

    public function get_label(): string
    {
        return __('Products', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user_products';
    }

    private function is_uniquely_purchased(Config $config): bool
    {
        return $config->get(UserProducts::NAME) === 'unique';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        if ($this->is_uniquely_purchased($config)) {
            $formatters->add(new Formatter\User\SoldUniqueProducts());
        } else {
            $formatters->add(new Formatter\User\SoldProducts());
        }

        $formatters->add(
            new Value\Formatter\User\Products(
                new Value\ExtendedValue\User\Products()
            )
        );

        return $formatters;
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        if ($this->is_uniquely_purchased($config)) {
            return new Sorting\User\ProductsUnique();
        }

        return new Sorting\User\Products();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\User\Products();
    }

}