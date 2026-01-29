<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\Formatter\Post\ExcerptRaw;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BeforeAfter;
use AC\Setting\ComponentFactory\StringLimit;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\FeatureSettingBuilder;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing\Setting\ComponentFactory\InlineEditContentTypeFactory;

class ShortDescription extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private InlineEditContentTypeFactory $content_editable_type;

    private StringLimit $string_limit;

    private BeforeAfter $before_after;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        InlineEditContentTypeFactory $content_editable_type,
        StringLimit $string_limit,
        BeforeAfter $before_after
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->content_editable_type = $content_editable_type;
        $this->string_limit = $string_limit;
        $this->before_after = $before_after;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->before_after->create($config))
                     ->add($this->string_limit->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new ExcerptRaw());
    }

    protected function get_feature_settings_builder(Config $config): FeatureSettingBuilder
    {
        return parent::get_feature_settings_builder($config)
                     ->set_edit(
                         $this->content_editable_type->create(
                             new ACP\Editing\Setting\ComponentFactory\EditableType\Content()
                         )
                     );
    }

    public function get_column_type(): string
    {
        return 'column-wc-product_short_description';
    }

    public function get_label(): string
    {
        return __('Short Description');
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $view = $config->get('editable_type') === 'textarea'
            ? new ACP\Editing\View\TextArea()
            : new ACP\Editing\View\Wysiwyg();

        return new ACP\Editing\Service\Basic(
            $view,
            new ACP\Editing\Storage\Post\Field('post_excerpt')
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Excerpt();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Post\Excerpt();
    }

}