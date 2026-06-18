<?php

namespace ACP\Editing\BulkDelete\Deletable;

use ACP\Editing\BulkDelete;
use ACP\Editing\BulkDelete\Deletable;
use ACP\Editing\RequestHandler;

class User implements Deletable
{

    public function get_delete_request_handler(): BulkDelete\RequestHandler\User
    {
        return new BulkDelete\RequestHandler\User();
    }

    public function user_can_delete(): bool
    {
        return current_user_can('delete_users');
    }

    public function get_query_request_handler(): RequestHandler\Query\User
    {
        return new RequestHandler\Query\User();
    }

}