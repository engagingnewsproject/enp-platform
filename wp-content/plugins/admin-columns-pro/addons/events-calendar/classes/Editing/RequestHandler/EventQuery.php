<?php

declare(strict_types=1);

namespace ACA\EC\Editing\RequestHandler;

use AC\Request;
use ACP;

class EventQuery extends ACP\Editing\RequestHandler\Query\Post
{

    public function handle(Request $request)
    {
        parent::handle($request);

        /**
         * The Events Calendar runs 'post_limits' to alter the limit for the admin list. In order for Export to work, we have to make sure the default 'WordPress' limit is used based on our query arguments
         */
        add_filter('post_limits', [$this, 'modify_posts_limit'], 1);
    }

    public function modify_posts_limit($limit): string
    {
        remove_filter('post_limits', ['Tribe__Events__Admin_List', 'events_search_limits']);

        return $limit;
    }

}