<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\DateFormat\Date;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC;
use ACA\EC\Setting\ComponentFactory\EventDates;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat;
use ACP\ConditionalFormat\FormattableConfig;
use ACP\Editing;
use ACP\Search;
use ACP\Sorting;
use ACP\Sorting\Type\DataType;

class DisplayDateFactory extends ACP\Column\AdvancedColumnFactory
{

    private EventDates $event_dates_factory;

    private Date $date_factory;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        EventDates $event_dates_factory,
        Date $date_factory
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->event_dates_factory = $event_dates_factory;
        $this->date_factory = $date_factory;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->event_dates_factory->create($config),
            $this->date_factory->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_display_date';
    }

    public function get_label(): string
    {
        return __('Date', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return (new FormatterCollection([
            new AC\Formatter\Post\Meta($this->get_meta_key($config)),
            new AC\Formatter\Date\Timestamp(),
        ]))->merge(parent::get_formatters($config));
    }

    private function get_meta_key(Config $config): string
    {
        return $config->get('event_date', '_EventStartDate');
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        switch ($this->get_meta_key($config)) {
            case '_EventStartDate':
                return new ACP\Editing\Service\Basic(
                    new ACP\Editing\View\DateTime(),
                    new EC\Editing\Storage\Event\StartDate()
                );
            case '_EventEndDate':
                return new ACP\Editing\Service\Basic(
                    new ACP\Editing\View\DateTime(),
                    new EC\Editing\Storage\Event\EndDate()
                );
            default:
                return null;
        }
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new Search\Comparison\Meta\DateTime\ISO(
            $this->get_meta_key($config),
            (new AC\Meta\QueryMetaFactory())->create_with_post_type($this->get_meta_key($config), 'tribe_events')
        );
    }

    protected function get_sorting(Config $config): ?Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta($this->get_meta_key($config), new DataType(DataType::DATETIME));
    }

    protected function get_conditional_format(Config $config): ?ConditionalFormat\FormattableConfig
    {
        return new FormattableConfig(
            new ConditionalFormat\Formatter\DateFormatter\DateValueFormatter(
                new FormatterCollection([
                    new AC\Formatter\Post\Meta((string)$config->get('event_date')),
                    new AC\Formatter\Date\DateFormat('Y-m-d', 'Y-m-d H:i:s'),
                ])
            )
        );
    }

}