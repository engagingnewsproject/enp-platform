<?php

declare(strict_types=1);

namespace ACP\Table;

use AC\ListScreen;
use ACP\ConditionalFormat\Settings\ListScreen\TableElementFactory;
use ACP\Editing\TableElement\BulkDelete;
use ACP\Editing\TableElement\BulkEdit;
use ACP\Editing\TableElement\InlineEdit;
use ACP\Export\TableElement\Export;
use ACP\Settings\ListScreen\TableElement\Search;

class TableSupport
{

    public static function is_export_enabled(ListScreen $list_screen): bool
    {
        return (new Export())->is_enabled($list_screen);
    }

    public static function is_inline_edit_enabled(ListScreen $list_screen): bool
    {
        return (new InlineEdit())->is_enabled($list_screen);
    }

    public static function is_bulk_edit_enabled(ListScreen $list_screen): bool
    {
        return (new BulkEdit())->is_enabled($list_screen);
    }

    public static function is_search_enabled(ListScreen $list_screen): bool
    {
        return (new Search())->is_enabled($list_screen);
    }

    public static function is_conditional_formatting_enabled(ListScreen $list_screen): bool
    {
        return (new TableElementFactory())->create()->is_enabled($list_screen);
    }

    public static function is_bulk_delete_enabled(ListScreen $list_screen): bool
    {
        return (new BulkDelete())->is_enabled($list_screen);
    }

}