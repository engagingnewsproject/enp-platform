<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class ProductTaxonomy extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private ComponentFactory\Product\ProductTaxonomy $product_taxonomy;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ComponentFactory\Product\ProductTaxonomy $product_taxonomy
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->product_taxonomy = $product_taxonomy;
    }

    public function get_column_type(): string
    {
        return 'column-order_product_taxonomy';
    }

    public function get_label(): string
    {
        return __('Product Taxonomy', 'codepress-admin-columns');
    }

    private function get_product_taxonomy(Config $config): TaxonomySlug
    {
        return new TaxonomySlug($config->get('taxonomy', 'product-category'));
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->product_taxonomy->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(
            new Formatter\Order\ProductTerms($this->get_product_taxonomy($config))
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopOrder\ProductTaxonomy((string)$this->get_product_taxonomy($config));
    }

}