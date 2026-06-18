<?php

declare(strict_types=1);

namespace ACP\ColumnFactories\Original;

use AC\TableScreen;
use ACP\ColumnFactory\Media\Original;

final class MediaFactory extends OriginalAdvancedColumnFactory
{

    protected function get_original_factories(TableScreen $table_screen): array
    {
        if ( ! $table_screen instanceof TableScreen\Media) {
            return [];
        }

        return [
            'parent' => Original\MediaParent::class,
            'title'  => Original\Title::class,
            'date'   => Original\Date::class,
        ];
    }

}