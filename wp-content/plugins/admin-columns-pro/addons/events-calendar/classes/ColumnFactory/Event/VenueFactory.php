<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC;
use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\NumberOfItems;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC;
use ACA\EC\Setting\ComponentFactory;
use ACA\EC\Value\Formatter\Event\VenueCollection;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

class VenueFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private const META_KEY = '_EventVenueID';

    private ComponentFactory\VenueDisplay $venue_display;

    private ComponentFactory\VenueLink $venue_link;

    private NumberOfItems $number_of_items;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        ComponentFactory\VenueDisplay $venue_display,
        ComponentFactory\VenueLink $venue_link,
        NumberOfItems $number_of_items
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->venue_display = $venue_display;
        $this->venue_link = $venue_link;
        $this->number_of_items = $number_of_items;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->venue_display->create($config),
            $this->venue_link->create($config),
            $this->number_of_items->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_venue';
    }

    public function get_label(): string
    {
        return __('Venue', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);
        $formatters->prepend(new VenueCollection());
        $formatters->add(new Separator(null, (int)$config->get('number_of_items') ?: 10));

        return $formatters;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new EC\Editing\Service\Event\Venue();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        switch ($config->get('post', '')) {
            case ComponentFactory\VenueDisplay::PROPERTY_CITY:
                return new ACP\Sorting\Model\Post\RelatedMeta\Post\Meta('_VenueCity', self::META_KEY);
            case ComponentFactory\VenueDisplay::PROPERTY_COUNTRY:
                return new ACP\Sorting\Model\Post\RelatedMeta\Post\Meta('_VenueCountry', self::META_KEY);
            case ComponentFactory\VenueDisplay::PROPERTY_WEBSITE:
                return new ACP\Sorting\Model\Post\RelatedMeta\Post\Meta('_VenueURL', self::META_KEY);
            default:
                return new ACP\Sorting\Model\Post\RelatedMeta\Post\Field('post_title', self::META_KEY);
        }
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new EC\Search\Event\Relation(self::META_KEY, new AC\Type\PostTypeSlug('tribe_venue'));
    }

}