<?php

declare(strict_types=1);

namespace ACP\Editing\Registerable;

use AC;
use AC\Asset\Location;
use AC\Asset\Style;
use AC\Registerable;
use ACP\Editing\Asset;
use ACP\Editing\BulkDelete;
use ACP\Editing\Encoder\TableDataEncoder;
use ACP\Editing\Factory\BulkEditFactory;
use ACP\Editing\Factory\InlineEditFactory;
use ACP\Editing\Preference;
use ACP\Export;
use ACP\Table\TableSupport;

class Scripts implements Registerable
{

    private Location\Absolute $location;

    private Export\ColumnRepository $column_repository;

    private BulkDelete\AggregateFactory $aggregate_factory_deletable;

    private InlineEditFactory $inline_edit_factory;

    private BulkEditFactory $bulk_edit_factory;

    public function __construct(
        Location\Absolute $location,
        Export\ColumnRepository $column_repository,
        BulkDelete\AggregateFactory $aggregate_factory_deletable,
        InlineEditFactory $inline_edit_factory,
        BulkEditFactory $bulk_edit_factory
    ) {
        $this->location = $location;
        $this->column_repository = $column_repository;
        $this->aggregate_factory_deletable = $aggregate_factory_deletable;
        $this->inline_edit_factory = $inline_edit_factory;
        $this->bulk_edit_factory = $bulk_edit_factory;
    }

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'register_scripts']);
    }

    public function register_scripts(AC\ListScreen $list_screen): void
    {
        $supports = [
            'inline_edit' => $this->inline_edit_factory->create($list_screen)->count() > 0,
            'bulk_edit'   => $this->bulk_edit_factory->create($list_screen)->count() > 0,
            'bulk_delete' => $this->is_bulk_delete_enabled($list_screen),
            'export'      => $this->is_export_enabled($list_screen),
        ];

        // Bail if nothing is supported
        if ( ! in_array(true, $supports, true)) {
            return;
        }

        $script = new Asset\Script\Table(
            'acp-editing-table',
            $this->location->with_suffix('assets/editing/js/table.js'),
            $list_screen,
            new TableDataEncoder($this->inline_edit_factory, $this->bulk_edit_factory),
            new Preference\EditState(),
            $supports
        );

        $script->enqueue();

        // CSS
        $style = new Style(
            'acp-editing-table',
            $this->location->with_suffix('assets/editing/css/table.css'),
            ['ac-utilities']
        );
        $style->enqueue();

        // Select 2
        wp_enqueue_script('ac-select2');
        wp_enqueue_style('ac-select2');

        // WP Media picker
        wp_enqueue_media();
        wp_enqueue_style('ac-jquery-ui');

        // WP Color picker
        wp_enqueue_script('wp-color-picker');
        wp_enqueue_style('wp-color-picker');

        // WP Content Editor
        wp_enqueue_editor();

        do_action('ac/table_scripts/editing', $list_screen);
    }

    public function is_export_enabled(AC\ListScreen $list_screen): bool
    {
        return $this->column_repository->find_all($list_screen)->count() > 0;
    }

    public function is_bulk_delete_enabled(AC\ListScreen $list_screen): bool
    {
        if ( ! TableSupport::is_bulk_delete_enabled($list_screen)) {
            return false;
        }

        $strategy = $this->aggregate_factory_deletable->create($list_screen->get_table_screen());

        if ( ! $strategy) {
            return false;
        }

        return $strategy->user_can_delete();
    }

}