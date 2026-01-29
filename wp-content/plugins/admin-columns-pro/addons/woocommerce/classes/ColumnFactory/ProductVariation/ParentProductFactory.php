<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\Formatter\Post\PostParentId;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\PostProperty;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Product\ProductProperty;
use ACA\WC\Setting\ComponentFactory\ProductVariation\ProductVariationLink;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class ParentProductFactory extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private ProductProperty $product_property;

    private ProductVariationLink $post_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ProductProperty $product_property,
        ProductVariationLink $post_link
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->product_property = $product_property;
        $this->post_link = $post_link;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->product_property->create($config))
                     ->add($this->post_link->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-parent_products';
    }

    public function get_label(): string
    {
        return __('Parent Product', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new PostParentId());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        switch ($config->get('product_property', '')) {
            case PostProperty::PROPERTY_ID:
                return new ACP\Search\Comparison\Post\Parent\Id();
            case PostProperty::PROPERTY_TITLE:
                return new ACP\Search\Comparison\Post\Parent\Title();
            case PostProperty::PROPERTY_STATUS:
                return new ACP\Search\Comparison\Post\Parent\Status();
            case ProductProperty::TYPE_SKU:
                return new ACP\Search\Comparison\Post\Parent\Meta\Text('_sku');
            case ProductProperty::TYPE_META:
                return $this->create_meta_comparison($config);
            default:
                return null;
        }
    }

    private function create_meta_comparison(Config $config)
    {
        $meta_key = $config->get('field', '');

        switch ($config->get('field_type')) {
            case 'excerpt':
            case 'text':
            case 'color':
            case 'url':
                return new ACP\Search\Comparison\Post\Parent\Meta\Text($meta_key);
            case 'numeric':
                return new ACP\Search\Comparison\Post\Parent\Meta\Number($meta_key);
            case 'user':
                return new ACP\Search\Comparison\Post\Parent\Meta\UserId($meta_key);
            case 'post':
                return new ACP\Search\Comparison\Post\Parent\Meta\PostId($meta_key);
            case 'media':
            case 'image':
                return new ACP\Search\Comparison\Post\Parent\Meta\PostId($meta_key, ['attachment']);
            default:
                return null;
        }
    }

}