<?php

declare(strict_types=1);

namespace ACA\WC\Service;

use AC;
use AC\Registerable;

class QuickAdd implements Registerable
{

    public function register(): void
    {
        add_filter('ac/quick_add/enable', [$this, 'disable_quick_add'], 10, 2);
    }

    public function disable_quick_add(bool $enabled, AC\TableScreen $table_screen): bool
    {
        if ($table_screen instanceof AC\PostType && $table_screen->get_post_type()->equals('shop_order')) {
            return false;
        }

        return $enabled;
    }

}