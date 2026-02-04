<?php

declare(strict_types=1);

namespace ACA\GravityForms\Export\Strategy;

use AC\Type\Value;
use AC\Type\ValueCollection;
use ACA\GravityForms;
use ACA\GravityForms\Utils\Hooks;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;

class Entry extends Strategy
{

    private ResponseFactory $response_factory;

    private TableDataFactory $table_data_factory;

    private GravityForms\TableScreen\Entry $table_screen;

    public function __construct(
        GravityForms\TableScreen\Entry $table_screen,
        TableDataFactory $table_data_factory,
        ResponseFactory $response_factory
    ) {
        $this->table_screen = $table_screen;
        $this->response_factory = $response_factory;
        $this->table_data_factory = $table_data_factory;
    }

    public function handle_export(): void
    {
        add_filter('gform_get_entries_args_entry_list', [$this, 'set_pagination_args'], 11);
        add_action(Hooks::get_load_form_entries(), [$this, 'delayed_export']);
    }

    public function delayed_export(): void
    {
        $table = $this->table_screen->get_list_table();
        $table->prepare_items();
        $values = [];

        foreach ($table->items as $item) {
            $values[] = new Value((int)$item['id']);
        }

        $this->response_factory->create(
            $this->table_data_factory->create(
                $this->columns,
                new ValueCollection(0, $values),
                0 === $this->counter
            )
        );

        exit;
    }

    public function set_pagination_args(array $args): array
    {
        $per_page = $this->get_items_per_iteration();

        $args['paging']['page_size'] = $per_page;
        $args['paging']['offset'] = $this->counter * $per_page;

        $ids = $this->ids;

        if ($ids) {
            $args['search_criteria']['field_filters'] = [
                [
                    'key'      => 'id',
                    'operator' => 'IN',
                    'value'    => $ids,
                ],
            ];
        }

        return $args;
    }

}