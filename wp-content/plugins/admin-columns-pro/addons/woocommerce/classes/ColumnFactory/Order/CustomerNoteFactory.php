<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\StringLimit;
use AC\Setting\ComponentFactory\UseIcon;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class CustomerNoteFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private $use_icon;

    private $string_limit;

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

    public function get_label(): string
    {
        return __('Customer Note', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-order_customer_note';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config)->prepend(new Formatter\Order\CustomerNote());

        if ($config->get('use_icon') === 'on') {
            $formatters->add(new Formatter\Order\CustomerNoteIcon());
        }

        return $formatters;
    }

}