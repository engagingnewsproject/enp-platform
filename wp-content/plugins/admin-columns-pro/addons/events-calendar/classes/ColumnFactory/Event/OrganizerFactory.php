<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Event;

use AC;
use AC\Formatter\Collection\Separator;
use AC\FormatterCollection;
use AC\MetaType;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\NumberOfItems;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\EC;
use ACA\EC\Setting\ComponentFactory\OrganizerDisplay;
use ACA\EC\Setting\ComponentFactory\OrganizerLink;
use ACA\EC\Value\Formatter\Event\OrganizerCollection;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting\Model\MetaFormatFactory;

class OrganizerFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private const META_KEY = '_EventOrganizerID';

    private OrganizerDisplay $organizer_display;

    private OrganizerLink $organizer_link;

    private NumberOfItems $number_of_items;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        OrganizerDisplay $organizer_display,
        OrganizerLink $organizer_link,
        NumberOfItems $number_of_items
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->organizer_display = $organizer_display;
        $this->organizer_link = $organizer_link;
        $this->number_of_items = $number_of_items;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return new ComponentCollection([
            $this->organizer_display->create($config),
            $this->organizer_link->create($config),
            $this->number_of_items->create($config),
        ]);
    }

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-event_organizer';
    }

    public function get_label(): string
    {
        return __('Organizer', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new OrganizerCollection())
                     ->add(new Separator(null, (int)$config->get('number_of_items', 10)));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new EC\Editing\Service\Event\Organizer();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new MetaFormatFactory())->create(
            new MetaType(MetaType::POST),
            self::META_KEY,
            new ACP\Sorting\FormatValue\PostTitle(),
            null,
            [
                'post_type' => 'tribe_events',
            ]
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new EC\Search\Event\Relation(self::META_KEY, new AC\Type\PostTypeSlug('tribe_organizer'));
    }

}