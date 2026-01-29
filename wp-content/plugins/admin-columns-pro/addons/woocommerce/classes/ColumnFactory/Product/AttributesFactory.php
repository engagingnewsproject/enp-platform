<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use AC\Type\TaxonomySlug;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Setting\ComponentFactory\Product\ProductAttributes;
use ACA\WC\Value\Formatter;
use ACA\WC\Value\Formatter\Product\AllProductAttributes;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Editing\Setting\ComponentFactory\InlineEditCreateTerms;

class AttributesFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private ProductAttributes $product_attributes;

    private InlineEditCreateTerms $create_terms;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ProductAttributes $product_attributes,
        InlineEditCreateTerms $create_terms
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->product_attributes = $product_attributes;
        $this->create_terms = $create_terms;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->product_attributes->create($config));
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        $builder = parent::get_feature_settings_builder($config);
        $attribute_key = $this->get_attribute_key($config);

        if ($this->is_taxonomy($attribute_key)) {
            $builder->set_edit($this->create_terms);
        }

        return $builder;
    }

    private function get_attribute_key(Config $config): string
    {
        return $config->get(ProductAttributes::NAME, '');
    }

    public function get_column_type(): string
    {
        return 'column-wc-attributes';
    }

    public function get_label(): string
    {
        return __('Attributes', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        $attribute_key = $this->get_attribute_key($config);

        if ($this->is_taxonomy($attribute_key)) {
            return $formatters->prepend(
                new Formatter\Product\Attributes\TaxonomyAttributes(
                    new TaxonomySlug($attribute_key)
                )
            );
        }

        if ($attribute_key) {
            return $formatters->prepend(
                new Formatter\Product\Attributes\CustomAttributes(
                    $attribute_key
                )
            );
        }

        return $formatters->prepend(new AllProductAttributes());
    }

    private function is_taxonomy(string $taxonomy_slug): bool
    {
        return taxonomy_exists($taxonomy_slug);
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        if ( ! $this->get_attribute_key($config)) {
            return new FormatterCollection([
                new AllProductAttributes(),
                new AC\Formatter\StripTags(),
                (new AC\Formatter\PregReplace())
                    ->replace_tabs('')
                    ->replace_multiple_spaces(' | '),
            ]);
        }

        // single atrribute export
        return parent::get_export($config);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        $attribute_key = $this->get_attribute_key($config);

        return $this->is_taxonomy($attribute_key)
            ? new ACP\Search\Comparison\Post\Taxonomy($attribute_key)
            : null;
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        $attribute_key = $this->get_attribute_key($config);

        return $this->is_taxonomy($attribute_key)
            ? new ACP\Sorting\Model\Post\Taxonomy($attribute_key)
            : null;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $attribute_key = $this->get_attribute_key($config);

        if ($this->is_taxonomy($attribute_key)) {
            return new Editing\Product\Attributes\Taxonomy(
                $attribute_key,
                'on' === $config->get('enable_term_creation', 'on')
            );
        }
        if ($attribute_key) {
            return new ACP\Editing\Service\Basic(
                new ACP\Editing\View\MultiInput(),
                new Editing\Storage\Product\Attributes\Custom(
                    $attribute_key
                )
            );
        }

        return null;
    }

    protected function get_conditional_format(Config $config): ?FormattableConfig
    {
        if ('' === $this->get_attribute_key($config)) {
            return null;
        }

        return new FormattableConfig(
            new ACP\ConditionalFormat\Formatter\FilterHtmlFormatter(
                new ACP\ConditionalFormat\Formatter\StringFormatter()
            )
        );
    }

}