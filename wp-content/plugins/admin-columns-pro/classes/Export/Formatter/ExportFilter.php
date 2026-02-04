<?php

declare(strict_types=1);

namespace ACP\Export\Formatter;

use AC\Column\Context;
use AC\Formatter;
use AC\TableScreen;
use AC\Type\Value;

class ExportFilter implements Formatter
{

    private Context $context;

    private TableScreen $table_screen;

    public function __construct(Context $context, TableScreen $table_screen)
    {
        $this->context = $context;
        $this->table_screen = $table_screen;
    }

    public function format(Value $value)
    {
        return $value->with_value(
            (string)apply_filters(
                'ac/export/render',
                (string)$value,
                $this->context,
                (string)$value->get_id(),
                $this->table_screen
            )
        );
    }

}