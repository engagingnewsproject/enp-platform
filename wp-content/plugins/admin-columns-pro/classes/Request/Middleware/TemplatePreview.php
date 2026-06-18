<?php

declare(strict_types=1);

namespace ACP\Request\Middleware;

use AC\ListScreen;
use AC\ListScreenRepository;
use AC\Middleware;
use AC\Request;
use AC\TableScreen;
use AC\Type\ListScreenId;
use Exception;
use RuntimeException;

class TemplatePreview implements Middleware
{

    private ListScreenRepository $storage;

    private TableScreen $table_screen;

    public function __construct(ListScreenRepository $storage, TableScreen $table_screen)
    {
        $this->storage = $storage;
        $this->table_screen = $table_screen;
    }

    private function get_requested_template(Request $request): ?ListScreen
    {
        try {
            $id = new ListScreenId((string)$request->get('layout'));
        } catch (Exception $e) {
            return null;
        }

        $list_screen = $this->storage->find($id);

        if ( ! $list_screen) {
            return null;
        }

        if ( ! $list_screen->get_table_id()->equals($this->table_screen->get_id())) {
            throw new RuntimeException('Invalid table screen.');
        }

        return $list_screen;
    }

    public function handle(Request $request): void
    {
        $template = $this->get_requested_template($request);

        if ( ! $template) {
            return;
        }

        $request->get_parameters()->merge([
            'list_screen' => $template,
        ]);
    }

}