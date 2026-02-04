<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Capabilities;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Response;
use AC\Type\ListScreenId;

class ListScreenDelete implements RequestAjaxHandler
{

    private Storage $storage;

    private AC\Nonce\Ajax $nonce;

    public function __construct(Storage $storage, AC\Nonce\Ajax $nonce)
    {
        $this->storage = $storage;
        $this->nonce = $nonce;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();
        $response = new Response\Json();

        if ( ! $this->nonce->verify($request)) {
            $response->error();
        }

        $list_screen = $this->storage->find(new ListScreenId($request->get('list_id')));

        if ( ! $list_screen) {
            return;
        }

        $this->storage->delete($list_screen);

        do_action('ac/list_screen/deleted', $list_screen);

        $response->set_message(
            sprintf(
                __('Table view %s successfully deleted.', 'codepress-admin-columns'),
                sprintf('<strong>"%s"</strong>', esc_html($list_screen->get_title()))
            )
        )->success();
    }

}