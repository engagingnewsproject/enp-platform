<?php

namespace ACP\Search\TableMarkup;

use ACP\Search\TableMarkup;

class User extends TableMarkup
{

    public function register(): void
    {
        add_action('restrict_manage_users', [$this, 'filters_markup'], 1);

        parent::register();
    }

    public function filters_markup()
    {
        remove_action('restrict_manage_users', [$this, __FUNCTION__], 1);

        parent::filters_markup();
    }

}