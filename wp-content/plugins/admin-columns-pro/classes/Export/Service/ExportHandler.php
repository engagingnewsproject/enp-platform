<?php

namespace ACP\Export\Service;

use AC\ColumnIterator;
use AC\ListScreen;
use AC\Registerable;
use AC\Request;
use AC\TableScreen;
use ACP\ColumnRepository;
use ACP\Export\Asset\Script\Table;
use ACP\Export\Strategy\AggregateFactory;

final class ExportHandler implements Registerable
{

    private AggregateFactory $factory;

    private ColumnRepository $column_repository;

    public function __construct(
        AggregateFactory $factory,
        ColumnRepository $column_repository
    ) {
        $this->factory = $factory;
        $this->column_repository = $column_repository;
    }

    public function register(): void
    {
        add_action('ac/table/list_screen', [$this, 'handle'], 10, 2);
    }

    public function handle(ListScreen $list_screen, TableScreen $table_screen): void
    {
        $request = new Request();

        if ('acp_export_listscreen_export' !== $request->get('acp_export_action')) {
            return;
        }

        if ( ! wp_verify_nonce($request->get('acp_export_nonce'), Table::NONCE_ACTION)) {
            return;
        }

        $counter = $this->get_export_counter($request);

        if ($counter === null) {
            wp_send_json_error(__('Invalid value supplied for export counter.', 'codepress-admin-columns'));
        }

        $strategy = $this->factory->create($table_screen);

        if ( ! $strategy) {
            return;
        }

        do_action('acp/export/before_batch');

        $strategy->set_ids($this->get_requested_ids($request))
                 ->set_columns($this->get_requested_columns($request, $list_screen))
                 ->set_counter($counter)
                 ->set_items_per_iteration($this->get_items_per_iteration());

        $strategy->handle_export();
    }

    private function get_items_per_iteration(): int
    {
        return (int)apply_filters('ac/export/exportable_list_screen/num_items_per_iteration', 250);
    }

    private function get_requested_ids(Request $request): array
    {
        $ids = $request->get('acp_export_ids');

        if (empty($ids)) {
            return [];
        }

        return array_map('absint', explode(',', $ids));
    }

    private function get_requested_columns(Request $request, ListScreen $list_screen): ColumnIterator
    {
        return $this->column_repository->find_all_with_export(
            $list_screen,
            $this->get_requested_column_names($request)
        );
    }

    private function get_requested_column_names(Request $request): ?array
    {
        $column_names = $request->get('acp_export_columns');

        if ( ! $column_names) {
            return null;
        }

        return explode(',', $column_names);
    }

    private function get_export_counter(Request $request): ?int
    {
        $counter = (int)$request->filter('acp_export_counter', 0, FILTER_SANITIZE_NUMBER_INT);

        return $counter >= 0
            ? $counter
            : null;
    }

}