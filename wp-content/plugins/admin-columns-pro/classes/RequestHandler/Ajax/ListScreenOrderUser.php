<?php

namespace ACP\RequestHandler\Ajax;

use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Storage\Repository\TableListOrder;
use AC\Type\TableId;

class ListScreenOrderUser implements RequestAjaxHandler
{

    private TableListOrder $preference_user;

    private Nonce\Ajax $nonce;

    public function __construct(TableListOrder $preference_user, Nonce\Ajax $nonce)
    {
        $this->preference_user = $preference_user;
        $this->nonce = $nonce;
    }

    public function handle(): void
    {
        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        $list_key = (string)$request->get('list_screen');
        $order = $request->filter('order', [], FILTER_DEFAULT, FILTER_REQUIRE_ARRAY);

        if ( ! $order || ! TableId::validate($list_key)) {
            wp_send_json_error();
        }

        $this->preference_user->set_order(
            new TableId($list_key),
            $order
        );

        wp_send_json_success();
    }

}