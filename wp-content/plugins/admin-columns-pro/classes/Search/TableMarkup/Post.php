<?php

namespace ACP\Search\TableMarkup;

use ACP\Search\TableMarkup;

class Post extends TableMarkup
{

    public function register(): void
    {
        add_action('restrict_manage_posts', [$this, 'filters_markup'], 1);

        parent::register();
    }

}