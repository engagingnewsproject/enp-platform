<?php

namespace ACP\Export\Service;

use AC;
use AC\Asset\Location;
use AC\Registerable;
use ACP\AdminColumnsPro;
use ACP\Export\Asset\Encoder;
use ACP\Export\Asset\Script\Table;
use ACP\Export\ColumnRepository;
use ACP\Export\Strategy\AggregateFactory;
use ACP\Export\UserPreference;

final class TableScreen implements Registerable
{

    protected Location\Absolute $location;

    private AggregateFactory $export_factory;

    private ColumnRepository $column_repository;

    private Encoder $encoder;

    public function __construct(
        AdminColumnsPro $plugin,
        AggregateFactory $factory,
        Encoder $encoder,
        ColumnRepository $column_repository
    ) {
        $this->location = $plugin->get_location();
        $this->export_factory = $factory;
        $this->column_repository = $column_repository;
        $this->encoder = $encoder;
    }

    public function register(): void
    {
        add_filter('ac/table/body_class', [$this, 'add_hide_export_button_class'], 10, 2);
        add_action('ac/table/list_screen', [$this, 'load_scripts']);
    }

    public function load_scripts(AC\ListScreen $list_screen): void
    {
        if ( ! $this->is_exportable($list_screen)) {
            return;
        }

        add_action('ac/table', [$this, 'register_screen_option']);
        add_action('ac/table/admin_footer', [$this, 'scripts']);
    }

    public function is_exportable(AC\ListScreen $list_screen): bool
    {
        return $this->column_repository->find_all($list_screen)->count() > 0;
    }

    public function scripts(AC\ListScreen $list_screen): void
    {
        $strategy = $this->export_factory->create(
            $list_screen->get_table_screen()
        );

        if ( ! $strategy) {
            return;
        }

        $style = new AC\Asset\Style(
            'acp-export-listscreen',
            $this->location->with_suffix('assets/export/css/listscreen.css')
        );
        $style->enqueue();

        $script = new Table(
            'acp-export-listscreen',
            $this->location->with_suffix('assets/export/js/listscreen.js'),
            $strategy->get_items_per_iteration(),
            $this->encoder->encode($list_screen),
            $this->get_export_button_setting($list_screen)
        );

        $script->enqueue();
    }

    public function register_screen_option(AC\Table\Screen $table): void
    {
        $list_screen = $table->get_list_screen();

        if ( ! $list_screen) {
            return;
        }

        $check_box = new AC\Form\Element\Checkbox('acp_export_show_export_button');
        $check_box->set_options([1 => __('Export Button', 'codepress-admin-columns')])
                  ->set_value($this->get_export_button_setting($list_screen) ? 1 : 0);

        $table->register_screen_option($check_box);
    }

    public function preferences(): UserPreference\ShowExportButton
    {
        return new UserPreference\ShowExportButton();
    }

    private function get_export_button_setting(AC\ListScreen $list_screen): bool
    {
        return $this->preferences()->is_active($list_screen->get_table_id());
    }

    public function add_hide_export_button_class($classes, $table)
    {
        $list_screen = $table->get_list_screen();

        if ($list_screen && ! $this->get_export_button_setting($list_screen)) {
            $classes .= ' ac-hide-export-button';
        }

        return $classes;
    }

}