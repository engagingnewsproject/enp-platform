<?php

declare(strict_types=1);

namespace ACA\EC\Editing\Strategy;

use ACA\EC\Editing\RequestHandler\EventQuery;
use ACP\Editing\RequestHandler;
use ACP\Editing\Strategy\Post;

class Event extends Post
{

    public function get_query_request_handler(): RequestHandler
    {
        return new EventQuery();
    }

}