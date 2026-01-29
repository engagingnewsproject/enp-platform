<?php

namespace ACP\Search\TableMarkup;

use ACP\Search\TableMarkup;

class MSUser extends TableMarkup
{

    public function register(): void
    {
        add_action('in_admin_footer', [$this, 'filters_markup'], 1);

        parent::register();
    }

    public function filters_markup()
    {
        remove_action('in_admin_footer', [$this, __FUNCTION__], 1);

        parent::filters_markup();
    }

}