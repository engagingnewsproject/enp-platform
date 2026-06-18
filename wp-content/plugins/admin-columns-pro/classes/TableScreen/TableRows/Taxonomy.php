<?php

declare(strict_types=1);

namespace ACP\TableScreen\TableRows;

use AC\Request;
use AC\TableScreen\TableRows;

final class Taxonomy extends TableRows
{

    public function register(): void
    {
        add_action('parse_term_query', [$this, 'handle_request']);
    }

    public function handle_request(): void
    {
        remove_action('parse_term_query', [$this, __FUNCTION__]);

        parent::handle(new Request());
    }

}