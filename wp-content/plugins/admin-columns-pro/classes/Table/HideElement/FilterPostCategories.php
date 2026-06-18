<?php

namespace ACP\Table\HideElement;

use ACP\Table\HideElement;

class FilterPostCategories implements HideElement
{

    public function hide(): void
    {
        add_filter('disable_categories_dropdown', '__return_true');
    }

}