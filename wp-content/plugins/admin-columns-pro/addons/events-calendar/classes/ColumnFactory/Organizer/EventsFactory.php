<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Organizer;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC\Search;
use ACA\EC\Setting\ComponentFactory\EventDisplay;
use ACA\EC\Value\ExtendedValue\OrganizerEvents;
use ACA\EC\Value\Formatter;
use ACA\EC\Value\Formatter\Count;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class EventsFactory extends ACP\Column\AdvancedColumnFactory
{

    private EventDisplay $event_display;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        EventDisplay $event_display
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->event_display = $event_display;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->event_display->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-organizer_events';
    }

    public function get_label(): string
    {
        return __('Events', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $display = $config->get('event_display', 'all');

        return new FormatterCollection([
            new Formatter\Organizer\RelatedEvents($display),
            new Count(),
            new Formatter\ExtendValueEventLink(
                new OrganizerEvents(),
                $display
            ),
        ]);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        switch ($config->get('event_display', 'all')) {
            case 'upcoming':
                return new Search\UpcomingEvent('_EventOrganizerID');
            case 'past':
                return new Search\PastEvents('_EventOrganizerID');
            default:
                return new Search\RelatedEvents('_EventOrganizerID');
        }
    }

}