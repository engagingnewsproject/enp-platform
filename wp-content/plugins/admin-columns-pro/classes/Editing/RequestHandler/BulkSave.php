<?php

namespace ACP\Editing\RequestHandler;

use AC;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\Response;
use AC\Type\ColumnId;
use AC\Type\ListScreenId;
use ACP;
use ACP\Editing\ApplyFilter;
use ACP\Editing\RequestHandler;
use ACP\Editing\RequestHandler\Exception\InvalidUserPermissionException;
use ACP\Editing\RequestHandler\Exception\NotEditableException;
use ACP\Editing\Service;
use ACP\Editing\Service\Editability;
use ACP\Editing\Strategy;
use RuntimeException;

class BulkSave implements RequestHandler
{

    private const SAVE_FAILED = 'failed';
    private const SAVE_SUCCESS = 'success';
    private const SAVE_NOTICE = 'not_editable';

    private Storage $storage;

    private Strategy\AggregateFactory $aggregate_factory;

    public function __construct(
        Storage $storage,
        Strategy\AggregateFactory $aggregate_factory
    ) {
        $this->storage = $storage;
        $this->aggregate_factory = $aggregate_factory;
    }

    public function handle(Request $request)
    {
        $response = new Response\Json();

        $ids = $request->filter('ids', false, FILTER_VALIDATE_INT, FILTER_REQUIRE_ARRAY);
        $form_data = $request->get('value', false);

        if ($ids === false || $form_data === false) {
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

        if ( ! $strategy->user_can_edit()) {
            $response->error();
        }

        $column = $list_screen->get_column(new ColumnId((string)$request->get('column')));

        if ( ! $column instanceof ACP\Column) {
            $response->error();
        }

        $service = $column->editing();

        if ( ! $service) {
            $response->error();
        }

        $results = [];

        foreach ($ids as $id) {
            $error = null;

            try {
                $this->save($id, $form_data, $strategy, $service, $column, $list_screen);
                $status = self::SAVE_SUCCESS;
            } catch (NotEditableException|InvalidUserPermissionException $e) {
                $error = $e->getMessage();
                $status = self::SAVE_NOTICE;
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
                $status = self::SAVE_FAILED;
            }

            $results[] = [
                'id'     => $id,
                'error'  => $error,
                'status' => $status,
            ];
        }

        $response
            ->set_parameter('results', $results)
            ->set_parameter('total', count($results))
            ->success();
    }

    private function save(
        $id,
        $form_data,
        Strategy $strategy,
        Service $service,
        AC\Column $column,
        ListScreen $list_screen
    ): void {
        $id = (int)$id;

        if ( ! $id) {
            throw new RuntimeException(__('Missing id', 'codepress-admin-columns'));
        }

        if ( ! $strategy->user_can_edit_item($id)) {
            throw new InvalidUserPermissionException();
        }

        if ($service instanceof Editability && ! $service->is_editable($id)) {
            throw new NotEditableException($service->get_not_editable_reason($id));
        }

        $context = $column->get_context();

        $filter = new ApplyFilter\SaveValue(
            $id,
            $column->get_context(),
            $list_screen->get_table_screen(),
            $list_screen->get_id()
        );
        $form_data = $filter->apply_filters($form_data);

        do_action('ac/editing/before_save', $context, $id, $form_data);

        $service->update(
            $id,
            $form_data
        );

        do_action('ac/editing/saved', $context, $id, $form_data, $list_screen);
    }

}