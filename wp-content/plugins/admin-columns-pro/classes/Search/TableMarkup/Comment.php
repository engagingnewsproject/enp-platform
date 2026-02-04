<?php

namespace ACP\Search\TableMarkup;

use ACP\Search\TableMarkup;

class Comment extends TableMarkup
{

    public function register(): void
    {
        add_action('restrict_manage_comments', [$this, 'filters_markup']);

        parent::register();
    }

}