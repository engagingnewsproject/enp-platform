<?php

declare(strict_types=1);

namespace ACA\EC\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\PostLink;

class EventLink extends PostLink
{

    public function __construct()
    {
        parent::__construct(__('Event', 'codepress-admin-columns'));
    }

    protected function get_display_options(): array
    {
        return array_intersect_key(parent::get_display_options(), array_flip(['', 'edit_post', 'view_post',]));
    }

}