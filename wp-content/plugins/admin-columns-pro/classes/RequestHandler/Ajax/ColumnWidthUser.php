<?php

namespace ACP\RequestHandler\Ajax;

use AC\ColumnSize;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\ColumnId;
use AC\Type\ColumnWidth;
use AC\Type\ListScreenId;
use InvalidArgumentException;
use LogicException;

class ColumnWidthUser implements RequestAjaxHandler
{

    private ColumnSize\UserStorage $user_storage;

    private Nonce\Ajax $nonce;

    public function __construct(ColumnSize\UserStorage $user_storage, Nonce\Ajax $nonce)
    {
        $this->user_storage = $user_storage;
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

        try {
            $width = new ColumnWidth('px', (int)$request->filter('width', 0, FILTER_VALIDATE_INT));
        } catch (InvalidArgumentException $e) {
            wp_send_json_error($e->getMessage());
        }

        $column_id = new ColumnId((string)$request->get('column_name'));

        $this->user_storage->save(
            $id,
            $column_id,
            $width
        );

        wp_send_json_success();
    }

}