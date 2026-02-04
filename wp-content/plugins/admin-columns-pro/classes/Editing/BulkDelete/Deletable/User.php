<?php

namespace ACP\Editing\BulkDelete\Deletable;

use ACP\Editing;
use ACP\Editing\BulkDelete\Deletable;

class User implements Deletable
{

    public function get_delete_request_handler(): Editing\BulkDelete\RequestHandler\User
    {
        return new Editing\BulkDelete\RequestHandler\User();
    }

    public function user_can_delete(): bool
    {
        return current_user_can('delete_users');
    }

    public function get_query_request_handler(): Editing\RequestHandler\Query\User
    {
        return new Editing\RequestHandler\Query\User();
    }

}