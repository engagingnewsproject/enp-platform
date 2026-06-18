<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC;
use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\StringLimit;
use AC\Setting\ComponentFactory\UseIcon;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter\Iconfy;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class PurchaseNoteFactory extends ACP\Column\AdvancedColumnFactory
{

    private const META_KEY = '_purchase_note';

    private UseIcon $use_icon;

    private StringLimit $string_limit;

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

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

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->string_limit->create($config))
                     ->add($this->use_icon->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-product_purchase_note';
    }

    public function get_label(): string
    {
        return __('Purchase Note', 'woocommerce');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config)->prepend(
            new Meta(self::META_KEY)
        );

        if ($config->get('use_icon', '') === 'on') {
            $formatters->add(new Iconfy());
        }

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            (new ACP\Editing\View\TextArea())->set_clear_button(true),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(new AC\Formatter\Post\Meta(self::META_KEY));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Text(self::META_KEY);
    }

}