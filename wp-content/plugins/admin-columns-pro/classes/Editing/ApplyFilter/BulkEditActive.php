<?php

namespace ACP\Editing\ApplyFilter;

use AC\Column\Context;
use AC\ListScreen;

class BulkEditActive
{

    private Context $context;

    private ListScreen $list_screen;

    public function __construct(Context $context, ListScreen $list_screen)
    {
        $this->context = $context;
        $this->list_screen = $list_screen;
    }

    public function apply_filters(bool $is_active): bool
    {
        return (bool)apply_filters('ac/editing/bulk/active', $is_active, $this->context, $this->list_screen);
    }

}