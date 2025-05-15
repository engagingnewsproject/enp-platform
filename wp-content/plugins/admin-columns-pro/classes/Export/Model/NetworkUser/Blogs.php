<?php

namespace ACP\Export\Model\NetworkUser;

use ACP\Export\Service;

class Blogs implements Service
{

    public function get_value($id)
    {
        $blogs = [];

        foreach (get_blogs_of_user($id) as $blog) {
            $blogs[] = $blog->siteurl;
        };

        return implode(', ', $blogs);
    }
}