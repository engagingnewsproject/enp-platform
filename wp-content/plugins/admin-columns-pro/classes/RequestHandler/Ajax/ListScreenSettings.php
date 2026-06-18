<?php

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Admin\Preference;
use AC\ListScreenRepository\Storage;
use ACP\ListScreenRepository\TemplateJsonFile;
use ACP\Request\Middleware\ListScreenAdmin;

class ListScreenSettings extends AC\RequestHandler\Ajax\ListScreenSettings
{

    private TemplateJsonFile $template_storage;

    public function __construct(
        Storage $storage,
        AC\TableScreenFactory\Aggregate $table_factory,
        Preference\EditorPreference $editor_preference,
        AC\Response\JsonListScreenSettingsFactory $response_factory,
        TemplateJsonFile $template_storage,
        AC\Type\ListScreenIdGenerator $list_screen_id_generator
    ) {
        parent::__construct(
            $storage,
            $table_factory,
            $editor_preference,
            $response_factory,
            $list_screen_id_generator
        );

        $this->template_storage = $template_storage;
    }

    protected function is_template(AC\ListScreen $list_screen): bool
    {
        return $this->template_storage->exists($list_screen->get_id());
    }

    protected function add_middleware(AC\Request $request, AC\TableScreen $table_screen): void
    {
        $request->add_middleware(
            new ListScreenAdmin(
                $this->storage,
                $table_screen,
                $this->editor_preference,
                $this->template_storage
            )
        );
    }

}