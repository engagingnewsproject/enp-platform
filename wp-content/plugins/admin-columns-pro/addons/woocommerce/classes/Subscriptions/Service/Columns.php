<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Service;

use AC;
use AC\Registerable;
use AC\Type\Group;
use ACA\WC\Search;

final class Columns implements Registerable
{

    public function register(): void
    {
        add_action('ac/column/groups', [$this, 'register_column_groups']);
    }

    public function register_column_groups(AC\Type\Groups $groups): void
    {
        $groups->add(
            new Group(
                'woocommerce_subscriptions',
                __('WooCommerce Subscriptions', 'codepress-admin-columns'),
                15
            )
        );
    }

}