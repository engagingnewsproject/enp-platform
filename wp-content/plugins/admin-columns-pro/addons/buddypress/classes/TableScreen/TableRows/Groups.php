<?php

declare(strict_types=1);

namespace ACA\BP\TableScreen\TableRows;

use AC;
use AC\Request;

class Groups extends AC\TableScreen\TableRows
{

    public function register(): void
    {
        add_action('bp_groups_admin_load', [$this, 'handle_request']);
    }

    public function handle_request(): void
    {
        $this->handle(new Request());
    }

}