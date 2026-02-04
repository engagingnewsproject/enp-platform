<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\DateFormat\Date;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class LastActive extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;

    private Date $date_format;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        Date $date_format
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->date_format = $date_format;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->date_format->create($config));
    }

    public function get_label(): string
    {
        return __('Last Active', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-last_active';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new AC\Formatter\User\Meta('wc_last_active'));
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\User\Meta('wc_last_active', ACP\Sorting\Type\DataType::create_numeric());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\DateTime\Timestamp(
            'wc_last_active',
            (new AC\Meta\QueryMetaFactory())->create('wc_last_active', AC\MetaType::create_user_meta())
        );
    }

    protected function get_conditional_format(Config $config): ?ACP\ConditionalFormat\FormattableConfig
    {
        return new ACP\ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\BaseDateFormatter(
                new FormatterCollection([
                    new AC\Formatter\User\Meta('wc_last_active'),
                ]),
                'U',
            )
        );
    }

}