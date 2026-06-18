<?php

namespace ACP\Editing\RequestHandler;

use AC\Request;
use AC\Response;
use AC\Type\TableId;
use ACP\Editing\Preference;
use ACP\Editing\RequestHandler;

class EditState implements RequestHandler
{

    private Preference\EditState $edit_state;

    public function __construct(Preference\EditState $edit_state)
    {
        $this->edit_state = $edit_state;
    }

    public function handle(Request $request)
    {
        $response = new Response\Json();

        $list_key = (string)$request->filter('list_screen', '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ( ! TableId::validate($list_key)) {
            $response->error();
        }

        $this->edit_state->set_status(
            new TableId($list_key),
            (bool)$request->get('value')
        );

        $response->success();
    }

}