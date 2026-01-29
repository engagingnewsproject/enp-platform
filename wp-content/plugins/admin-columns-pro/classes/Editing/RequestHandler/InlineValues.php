<?php

namespace ACP\Editing\RequestHandler;

use AC;
use AC\Column;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\Response;
use AC\Type\ColumnId;
use AC\Type\ListScreenId;
use ACP;
use ACP\Editing\ApplyFilter\EditValue;
use ACP\Editing\RequestHandler;
use ACP\Editing\Service\Editability;
use ACP\Editing\Strategy\AggregateFactory;

class InlineValues implements RequestHandler
{

    private Storage $storage;

    private AggregateFactory $aggregate_factory;

    public function __construct(Storage $storage, AggregateFactory $aggregate_factory)
    {
        $this->storage = $storage;
        $this->aggregate_factory = $aggregate_factory;
    }

    public function handle(Request $request)
    {
        $response = new Response\Json();

        $ids = $request->filter('ids', [], FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);

        if (empty($ids)) {
            $response->error();
        }

        $list_id = $request->get('layout');

        if ( ! ListScreenId::is_valid_id($list_id)) {
            $response->error();
        }

        $list_screen = $this->storage->find(new ListScreenId($list_id));

        if ( ! $list_screen || ! $list_screen->is_user_allowed(wp_get_current_user())) {
            $response->error();
        }

        $strategy = $this->aggregate_factory->create(
            $list_screen->get_table_screen()
        );

        if ( ! $strategy) {
            $response->error();
        }

        foreach ($ids as $k => $id) {
            if ( ! $strategy->user_can_edit_item((int)$id)) {
                unset($ids[$k]);
            }
        }

        $column_id = (string)$request->get('column');

        $column = ColumnId::is_valid_id($column_id)
            ? $list_screen->get_column(new ColumnId($column_id))
            : null;

        $values = $column
            ? $this->get_values_by_column($column, $ids, $list_screen)
            : $this->get_values_by_list_screen($list_screen, $ids);

        $response
            ->set_parameter('editable_values', $values)
            ->success();
    }

    private function get_values_by_list_screen(AC\ListScreen $list_screen, array $ids): array
    {
        $values = [];

        foreach ($list_screen->get_columns() as $column) {
            $values[] = $this->get_values_by_column($column, $ids, $list_screen);
        }

        return array_merge(...$values);
    }

    private function get_values_by_column(Column $column, array $ids, AC\ListScreen $list_screen): array
    {
        if ( ! $column instanceof ACP\Column) {
            return [];
        }

        $setting = $column->get_setting('edit');

        if ( ! $setting instanceof AC\Setting\Component || ! $setting->has_input()) {
            return [];
        }

        if ('on' !== $setting->get_input()->get_value()) {
            return [];
        }

        $service = $column->editing();

        if ( ! $service) {
            return [];
        }

        $context = $column->get_context();

        $values = [];

        foreach ($ids as $id) {
            $id = (int)$id;

            if ($service instanceof Editability && ! $service->is_editable($id)) {
                continue;
            }

            $filter = new EditValue(
                $id,
                $context,
                $list_screen->get_table_screen(),
                $list_screen->get_id()
            );
            $value = $filter->apply_filters($service->get_value($id));

            // Not editable. Backwards compatibility.
            if (null === $value) {
                continue;
            }

            // Some non-existing values can be set to false
            if (false === $value) {
                $value = '';
            }

            $values[] = [
                'id'          => $id,
                'column_name' => (string)$column->get_id(),
                'value'       => $value,
            ];
        }

        return $values;
    }

}