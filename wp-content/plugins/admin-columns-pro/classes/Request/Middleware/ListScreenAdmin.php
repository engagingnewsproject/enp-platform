<?php

namespace ACP\Request\Middleware;

use AC\Admin\Preference;
use AC\ListScreen;
use AC\ListScreenRepository;
use AC\Request;
use AC\TableScreen;
use AC\Type\ListScreenId;
use ACP\ListScreenRepository\TemplateJsonFile;

/**
 * Middleware for handling list screen requests.
 * Difference between the default middleware is that it also handles requests for templates.
 */
class ListScreenAdmin extends Request\Middleware\ListScreenAdmin
{

    private TemplateJsonFile $template_storage;

    public function __construct(
        ListScreenRepository\Storage $storage,
        TableScreen $table_screen,
        Preference\EditorPreference $preference,
        TemplateJsonFile $template_storage
    ) {
        parent::__construct($storage, $table_screen, $preference);

        $this->template_storage = $template_storage;
    }

    protected function get_requested_listscreen(Request $request): ?ListScreen
    {
        $id = $this->get_requested_listscreen_id($request);

        if ( ! $id) {
            return null;
        }

        return $this->get_list_screen_by_id($id) ?: $this->get_template_by_id($id);
    }

    private function get_template_by_id(ListScreenId $id): ?ListScreen
    {
        $list_screen = $this->template_storage->find($id);

        return $list_screen && $this->table_screen->get_id()->equals($list_screen->get_table_id())
            ? $list_screen
            : null;
    }

}