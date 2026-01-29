<?php

namespace ACP\QuickAdd\Table;

use AC\Asset\Location;
use AC\Registerable;
use AC\Table;
use ACP\QuickAdd\Admin\TableElement;
use ACP\QuickAdd\Filter;
use ACP\QuickAdd\Model;
use ACP\QuickAdd\Table\Checkbox\ShowButton;

class Loader implements Registerable
{

    private $location;

    private $table_element;

    private $preference;

    private $filter;

    public function __construct(
        Location $location,
        TableElement\QuickAdd $table_element,
        Preference\ShowButton $preference,
        Filter $filter
    ) {
        $this->location = $location;
        $this->table_element = $table_element;
        $this->preference = $preference;
        $this->filter = $filter;
    }

    public function register(): void
    {
        add_action('ac/table', [$this, 'load']);
    }

    public function load(Table\Screen $table_screen)
    {
        $list_screen = $table_screen->get_list_screen();

        if ( ! $list_screen) {
            return;
        }

        $table = $list_screen->get_table_screen();

        if ( ! $this->filter->match($table)) {
            return;
        }

        $model = Model\Factory::create($table);

        if ( ! $model || ! $model->has_permission(wp_get_current_user())) {
            return;
        }

        if ( ! $this->table_element->is_enabled($list_screen)) {
            return;
        }

        $table_screen->register_screen_option(
            new ShowButton($this->preference->is_active($list_screen->get_table_id()) ? 1 : 0)
        );

        $script = new Script\AddNewInline(
            __('Add Row', 'codepress-admin-columns'),
            'aca-add-new-inline',
            $this->location->with_suffix('assets/add-new-inline/js/table.js')
        );
        $script->enqueue();
    }

}