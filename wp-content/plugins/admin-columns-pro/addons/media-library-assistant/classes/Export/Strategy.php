<?php

declare(strict_types=1);

namespace ACA\MLA\Export;

use AC\ThirdParty\MediaLibraryAssistant\WpListTableFactory;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACP;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;

class Strategy extends ACP\Export\Strategy
{

    private ResponseFactory $response_factory;

    private TableDataFactory $table_data_factory;

    public function __construct(
        ResponseFactory $response_factory,
        TableDataFactory $table_data_factory
    ) {
        $this->response_factory = $response_factory;
        $this->table_data_factory = $table_data_factory;
    }

    public function handle_export(): void
    {
        add_filter('mla_list_table_query_final_terms', [$this, 'query'], 1e6);
        add_action('mla_list_table_prepare_items', [$this, 'prepare_items'], 10, 2);

        add_filter('posts_clauses', [$this, 'filter_ids']);

        // Trigger above hooks early by initiating list table. This prevents "headers already sent".
        (new WpListTableFactory())->create()->prepare_items();
    }

    public function filter_ids($clauses)
    {
        global $wpdb;

        if ($this->ids) {
            $clauses['where'] .= sprintf("\nAND $wpdb->posts.ID IN( %s )", implode(',', $this->ids));
        }

        return $clauses;
    }

    public function query($request)
    {
        $request['offset'] = $this->counter * $this->items_per_iteration;
        $request['posts_per_page'] = $this->items_per_iteration;
        $request['posts_per_archive_page'] = $this->items_per_iteration;

        return $request;
    }

    public function prepare_items($query): void
    {
        $values = array_map(
            static function ($item) {
                return new Value((int)$item->ID);
            },
            $query->items
        );

        $data = $this->table_data_factory->create(
            $this->columns,
            new ValueCollection(0, $values),
            0 === $this->counter
        );

        $this->response_factory->create(
            $data
        );
        exit;
    }

}