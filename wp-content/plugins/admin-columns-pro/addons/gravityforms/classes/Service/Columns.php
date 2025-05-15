<?php

namespace ACA\GravityForms\Service;

use AC\ListScreen;
use AC\Registerable;
use ACA\GravityForms\Column;

class Columns implements Registerable
{

    public function register(): void
    {
        add_action('ac/column_types', [$this, 'register_columns']);
    }

    public function register_columns(ListScreen $list_screen): void
    {
        if ($list_screen instanceof ListScreen\Post) {
            $list_screen->register_column_type(new Column\Post\Form());
        }
    }

}