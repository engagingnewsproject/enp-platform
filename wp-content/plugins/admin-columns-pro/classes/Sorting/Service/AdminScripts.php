<?php

declare(strict_types=1);

namespace ACP\Sorting\Service;

use AC;
use AC\Asset\Location;
use AC\Asset\Script;
use AC\Asset\Style;
use AC\Column\ColumnLabelTrait;
use AC\Type\ColumnId;
use ACP\Sorting\Type\SortType;

class AdminScripts implements AC\Registerable
{

    use ColumnLabelTrait;

    private Location\Absolute $location;

    private AC\ListScreen $list_screen;

    public function __construct(Location\Absolute $location, AC\ListScreen $list_screen)
    {
        $this->location = $location;
        $this->list_screen = $list_screen;
    }

    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'scripts']);
    }

    public function scripts(): void
    {
        $sort_type = SortType::create_by_request_globals();

        if ( ! $sort_type) {
            return;
        }

        if ($this->is_sorted_by_default($sort_type)) {
            return;
        }

        $column = $this->list_screen->get_column(
            new ColumnId($sort_type->get_order_by())
        );

        if ( ! $column) {
            return;
        }

        $script = new Script('acp-sorting', $this->location->with_suffix('assets/sorting/js/table.js'));
        $script->add_inline_variable('acp_sorting_i18n', [
            'reset_sorting' => __('Reset Sorting', 'codepress-admin-columns'),
            'tooltip'       => sprintf(__('Sorted by %s', 'codepress-admin-columns'), $this->get_column_label($column)),
        ]);
        $script->enqueue();

        $style = new Style('acp-sorting', $this->location->with_suffix('assets/sorting/css/table.css'));
        $style->enqueue();
    }

    private function is_sorted_by_default(SortType $sort_type): bool
    {
        $default = SortType::create_by_list_screen($this->list_screen);

        return $default && $default->equals($sort_type);
    }

}