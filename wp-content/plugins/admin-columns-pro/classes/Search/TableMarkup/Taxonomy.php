<?php

namespace ACP\Search\TableMarkup;

use ACP\Search\TableMarkup;

class Taxonomy extends TableMarkup
{

    public function register(): void
    {
        add_action('in_admin_footer', [$this, 'filters_markup'], 1);

        parent::register();
    }

}