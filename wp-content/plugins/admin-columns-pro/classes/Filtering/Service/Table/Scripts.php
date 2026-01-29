<?php

declare(strict_types=1);

namespace ACP\Filtering\Service\Table;

use AC;
use AC\Asset;
use AC\Registerable;
use ACP\AdminColumnsPro;
use ACP\Column;
use ACP\Filtering\Asset\TableScriptFactory;
use ACP\Filtering\DefaultFilters\Aggregate;
use ACP\Filtering\OptionsFactory;
use ACP\Settings\ListScreen\TableElement;

class Scripts implements Registerable
{

    private $location;

    private $options_factory;

    private $request;

    private Aggregate $default_filters;

    public function __construct(
        AdminColumnsPro $plugin,
        OptionsFactory $options_factory,
        AC\Request $request,
        Aggregate $default_filters
    ) {
        $this->location = $plugin->get_location();
        $this->options_factory = $options_factory;
        $this->request = $request;
        $this->default_filters = $default_filters;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'scripts'], 1);
    }

    public function scripts(AC\ListScreen $list_screen): void
    {
        $default_filters = $this->default_filters->create($list_screen->get_table_screen());

        if ( ! $this->is_enabled($list_screen) && ! $default_filters) {
            return;
        }

        $style = new Asset\Style('acp-filtering-table', $this->location->with_suffix('assets/filtering/css/table.css'));
        $style->enqueue();

        $script = (new TableScriptFactory(
            $this->location,
            $this->options_factory,
            $this->request,
            $default_filters
        ))->create(
            $list_screen
        );
        $script->enqueue();
    }

    private function is_enabled(AC\ListScreen $list_screen): bool
    {
        $filters = new TableElement\Filters();

        if ( ! $filters->is_enabled($list_screen)) {
            return false;
        }

        foreach ($list_screen->get_columns() as $column) {
            if ( ! $column instanceof Column) {
                continue;
            }

            $comparison = $column->search();

            if ( ! $comparison) {
                continue;
            }

            $setting = $column->get_setting('filter');

            if ( ! $setting instanceof AC\Setting\Component) {
                continue;
            }

            if ($setting->has_input() && $setting->get_input()->get_value() === 'on') {
                return true;
            }
        }

        return false;
    }

}