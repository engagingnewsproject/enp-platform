<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\StringLimit;
use AC\Setting\ComponentFactory\UseIcon;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Editing;

class CustomerNote extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private UseIcon $use_icon;

    private StringLimit $string_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        StringLimit $string_limit,
        UseIcon $use_icon
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->string_limit = $string_limit;
        $this->use_icon = $use_icon;
    }

    public function get_column_type(): string
    {
        return 'column-wc-order_customer_note';
    }

    public function get_label(): string
    {
        return __('Customer Note', 'codepress-admin-columns');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->use_icon->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config)
                            ->prepend(new Formatter\Order\CustomerNote());

        if ($config->get('use_icon') === 'on') {
            $formatters->add(new Formatter\Order\CustomerNoteIcon());
        }

        return $formatters;
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new Formatter\Order\CustomerNote());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\Service\Basic(
            (new Editing\View\TextArea())->set_placeholder(__('Customer notes about the order', 'woocommerce')),
            new Editing\Storage\Post\Field('post_excerpt')
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopOrder\CustomerMessage();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\PostField('post_excerpt');
    }

}