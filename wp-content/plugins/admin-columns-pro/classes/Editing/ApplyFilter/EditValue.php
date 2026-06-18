<?php

namespace ACP\Editing\ApplyFilter;

use AC\Column\Context;
use AC\TableScreen;
use AC\Type\ListScreenId;

class EditValue
{

    private int $id;

    private Context $context;

    private TableScreen $table_screen;

    private ListScreenId $list_screen_id;

    public function __construct(int $id, Context $context, TableScreen $table_screen, ListScreenId $list_screen_id)
    {
        $this->id = $id;
        $this->context = $context;
        $this->table_screen = $table_screen;
        $this->list_screen_id = $list_screen_id;
    }

    public function apply_filters($value)
    {
        return apply_filters(
            'ac/editing/input_value',
            $value,
            $this->context,
            $this->id,
            $this->table_screen,
            $this->list_screen_id
        );
    }

}