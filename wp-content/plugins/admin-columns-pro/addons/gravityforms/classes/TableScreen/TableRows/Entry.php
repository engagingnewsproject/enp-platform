<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableScreen\TableRows;

use AC;
use AC\Request;
use ACA\GravityForms\Utils\Hooks;

class Entry extends AC\TableScreen\TableRows
{

    public function register(): void
    {
        add_action(
            Hooks::get_load_form_entries(),
            function (): void {
                $request = new Request();

                if ($this->is_request($request)) {
                    $this->handle($request);
                }
            }
        );
    }

}