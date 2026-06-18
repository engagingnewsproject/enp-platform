<?php

declare(strict_types=1);

namespace ACA\SeoPress\TableScreen;

use AC\TableScreen\Post;
use AC\Type\Uri;

class Redirect extends Post
{

    public function get_url(): Uri
    {
        return parent::get_url()->with_arg('post_status', 'redirects');
    }

}