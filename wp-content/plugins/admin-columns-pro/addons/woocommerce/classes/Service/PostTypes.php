<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC\Registerable;

class PostTypes implements Registerable
{

    public function register(): void
    {
        add_filter('ac/post_types', [$this, 'deregister_post_type']);
    }

    public function deregister_post_type(array $post_types): array
    {
        unset($post_types['shop_order']);
        unset($post_types['shop_subscription']);

        return $post_types;
    }

}