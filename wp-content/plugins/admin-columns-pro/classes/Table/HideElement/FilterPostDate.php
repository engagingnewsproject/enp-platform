<?php

namespace ACP\Table\HideElement;

use ACP\Table\HideElement;

class FilterPostDate implements HideElement
{

    public function hide(): void
    {
        add_filter('disable_months_dropdown', '__return_true');
    }

}