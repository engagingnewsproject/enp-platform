<?php

namespace ACP\Table\HideElement;

use ACP\Table\HideElement;

class FilterCommentTypes implements HideElement
{

    public function hide(): void
    {
        add_filter('admin_comment_types_dropdown', function () {
            return [];
        });
    }

}