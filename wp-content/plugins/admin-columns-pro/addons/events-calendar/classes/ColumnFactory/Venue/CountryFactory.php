<?php

declare(strict_types=1);

namespace ACA\EC\ColumnFactory\Venue;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACP;
use Tribe__View_Helpers;

class CountryFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    private const META_KEY = '_VenueCountry';

    protected function get_group(): ?string
    {
        return 'events_calendar';
    }

    public function get_column_type(): string
    {
        return 'column-ec-venue_country';
    }

    public function get_label(): string
    {
        return __('Country', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return new FormatterCollection([
            new AC\Formatter\Post\Meta(self::META_KEY),
        ]);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Select(self::META_KEY, $this->get_countries());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select($this->get_countries()),
            new ACP\Editing\Storage\Post\Meta(self::META_KEY)
        );
    }

    private function get_countries(): array
    {
        if ( ! class_exists('Tribe__View_Helpers')) {
            return [];
        }

        $countries = Tribe__View_Helpers::constructCountries();

        return array_combine($countries, $countries);
    }

}