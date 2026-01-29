<?php

namespace ACP\Search;

use AC;

class TableScreenOptions implements AC\Registerable
{

    public const INPUT_NAME = 'acp_enable_smart_filtering_button';

    private $preferences;

    private $table_element_smart_filters;

    private $location;

    public function __construct(
        AC\Asset\Location\Absolute $location,
        Preferences\SmartFiltering $preferences,
        Settings\TableElement\SmartFilters $table_element_smart_filters
    ) {
        $this->location = $location;
        $this->preferences = $preferences;
        $this->table_element_smart_filters = $table_element_smart_filters;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'scripts']);
        add_action('ac/table', [$this, 'register_screen_option']);
    }

    private function is_active(AC\ListScreen $list_screen): bool
    {
        return $this->preferences->is_active($list_screen->get_table_id());
    }

    public function register_screen_option(AC\Table\Screen $table): void
    {
        $list_screen = $table->get_list_screen();

        if ( ! $list_screen) {
            return;
        }

        if ( ! TableScreenSupport::is_searchable($list_screen->get_table_screen())) {
            return;
        }

        if ( ! $this->table_element_smart_filters->is_enabled($list_screen)) {
            return;
        }

        $check_box = new AC\Form\Element\Checkbox(self::INPUT_NAME);

        $check_box->set_options([1 => __('Smart Filtering', 'codepress-admin-columns')])
                  ->set_value($this->is_active($list_screen) ? 1 : 0);

        $table->register_screen_option($check_box);
    }

    public function scripts(AC\ListScreen $list_screen): void
    {
        if ( ! TableScreenSupport::is_searchable($list_screen->get_table_screen())) {
            return;
        }

        $script = new AC\Asset\Script(
            'acp-search-table-screen-options',
            $this->location->with_suffix('assets/search/js/screen-options.bundle.js'),
            ['ac-table']
        );
        $script->enqueue();
    }

}