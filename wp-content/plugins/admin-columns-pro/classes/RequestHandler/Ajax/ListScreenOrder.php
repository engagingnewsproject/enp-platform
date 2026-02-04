<?php

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Capabilities;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\TableId;

class ListScreenOrder implements RequestAjaxHandler
{

    private AC\Storage\Repository\ListScreenOrder $list_screen_order;

    private AC\Nonce\Ajax $nonce;

    public function __construct(AC\Storage\Repository\ListScreenOrder $order, AC\Nonce\Ajax $nonce)
    {
        $this->list_screen_order = $order;
        $this->nonce = $nonce;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        $list_key = $request->get('list_key');

        if ( ! TableId::validate($list_key)) {
            wp_send_json_error();
        }

        $order = json_decode($request->filter('order', ''));

        if ( ! $order) {
            wp_send_json_error();
        }

        $this->list_screen_order->set(new TableId($list_key), $order);

        wp_send_json_success();
    }
}