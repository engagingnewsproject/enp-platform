<?php

declare(strict_types=1);

namespace ACA\MetaBox\Service;

use AC;
use AC\PostType;
use AC\Registerable;

final class QuickAdd implements Registerable
{

    public function register(): void
    {
        add_filter('ac/quick_add/enable', [$this, 'disable_quick_add'], 10, 2);
    }

    public function disable_quick_add($enabled, AC\TableScreen $list_screen)
    {
        if (
            $list_screen instanceof PostType
            && in_array((string)$list_screen->get_post_type(), ['meta-box', 'mb-post-type', 'mb-taxonomy', 'mb-views'])
        ) {
            $enabled = false;
        }

        return $enabled;
    }

}