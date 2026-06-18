<?php

namespace ACP\Export\TableElement;

use AC\ListScreen;
use ACP;

class Export extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct('hide_export', __('Export', 'codepress-admin-columns'), 'feature');
    }

    public function is_enabled(ListScreen $list_screen): bool
    {
        $is_active = parent::is_enabled($list_screen);

        return (new ACP\Export\ApplyFilter\ListScreenActive($list_screen))->apply_filters($is_active);
    }

}