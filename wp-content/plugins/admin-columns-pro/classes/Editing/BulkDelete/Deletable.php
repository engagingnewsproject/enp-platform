<?php

namespace ACP\Editing\BulkDelete;

use ACP;

interface Deletable
{

    public function get_delete_request_handler(): RequestHandler;

    public function get_query_request_handler(): ACP\Editing\RequestHandler;

    public function user_can_delete(): bool;

}