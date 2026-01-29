<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\Service;

use AC\Registerable;

class PostTypes implements Registerable
{

    public function register(): void
    {
        add_filter('ac/post_types', [$this, 'deregister_global_post_type']);
    }

    public function deregister_global_post_type($post_types)
    {
        unset($post_types['fl-builder-template']);

        return $post_types;
    }

}