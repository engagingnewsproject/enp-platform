<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableElement;

use ACP\Settings\ListScreen\TableElement;

class WordPressNotifications extends TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_gf_wordpress_notices',
            __('WordPress Notifications', 'codepress-admin-columns'),
            'element'
        );
    }

}