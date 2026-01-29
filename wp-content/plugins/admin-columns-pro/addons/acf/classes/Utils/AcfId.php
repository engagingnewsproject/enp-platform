<?php

declare(strict_types=1);

namespace ACA\ACF\Utils;

use AC\MetaType;
use AC\Type\TableScreenContext;

class AcfId
{

    public static function get_id($id, TableScreenContext $table_screen_context): string
    {
        return self::get_prefix($table_screen_context) . $id;
    }

    public static function get_prefix(TableScreenContext $table_context): string
    {
        switch ($table_context->get_meta_type()->get()) {
            case MetaType::USER:
                return 'user_';
            case MetaType::COMMENT:
                return 'comment_';
            case MetaType::SITE:
                return 'site_';
            case MetaType::TERM:
                return $table_context->has_taxonomy() ? $table_context->get_taxonomy() . '_' : 'tax_';
            default:
                return '';
        }
    }

}