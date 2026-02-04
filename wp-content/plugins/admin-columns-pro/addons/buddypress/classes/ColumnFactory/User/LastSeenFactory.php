<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User;

use AC\Formatter\Date\Timestamp;
use AC\Formatter\Date\WordPressDateFormat;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\BP\Settings\ComponentFactory\Date;
use ACA\BP\Value\Formatter\User\LastSeenDate;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;
use ACP\Sorting;

class LastSeenFactory extends ACP\Column\AdvancedColumnFactory
{

    private Date $date_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        Date $date_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->date_factory = $date_factory;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->date_factory->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'buddypress';
    }

    public function get_column_type(): string
    {
        return 'column-buddypress_user_last_seen';
    }

    public function get_label(): string
    {
        return __('Last Seen', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return (new FormatterCollection([
            new LastSeenDate(),
        ]))->merge(parent::get_formatters($config));
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new Sorting\Model\User\Meta('last_activity');
    }

    protected function get_conditional_format(Config $config): ?ConditionalFormat\FormattableConfig
    {
        return new ConditionalFormat\FormattableConfig(
            new ACP\ConditionalFormat\Formatter\DateFormatter\FormatFormatter()
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new LastSeenDate(),
            new Timestamp(),
            new WordPressDateFormat('Y-m-d H:i:s', 'U'),
        ]);
    }

}