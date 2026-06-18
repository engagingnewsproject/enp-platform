<?php

namespace ACP\RequestHandler\Ajax;

use AC\ColumnSize\UserStorage;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Storage\Repository\UserColumnOrder;
use AC\Type\ListScreenId;
use LogicException;

class ColumnReset implements RequestAjaxHandler
{

    private UserColumnOrder $storage_order;

    private UserStorage $storage_size;

    private Nonce\Ajax $nonce;

    public function __construct(UserColumnOrder $storage_order, UserStorage $storage_size, Nonce\Ajax $nonce)
    {
        $this->storage_order = $storage_order;
        $this->storage_size = $storage_size;
        $this->nonce = $nonce;
    }

    public function handle(): void
    {
        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        try {
            $id = new ListScreenId($request->get('list_id'));
        } catch (LogicException $e) {
            wp_send_json_error();
        }

        $this->storage_order->delete($id);
        $this->storage_size->delete_by_list_id($id);

        wp_send_json_success();
    }

}