<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Organizer;

use AC;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC;
use ACA\EC\Setting\ComponentFactory\EventLink;
use ACA\EC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class UpcomingEventFactory extends ACP\Column\AdvancedColumnFactory
{

    private EventLink $event_link;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        EventLink $event_link
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->event_link = $event_link;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->event_link->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-organizer_upcoming_event';
    }

    public function get_label(): string
    {
        return __('Upcoming Event', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return (new FormatterCollection([
            new Formatter\Organizer\UpcomingEvent(),
            new AC\Formatter\Post\PostTitle(),
        ]))->merge(parent::get_formatters($config));
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new EC\Search\UpcomingEvent('_EventOrganizerID');
    }

}