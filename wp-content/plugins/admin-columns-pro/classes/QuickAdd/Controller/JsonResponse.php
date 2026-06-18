<?php

namespace ACP\QuickAdd\Controller;

use AC\Response\Json;
use AC\TableScreen;

class JsonResponse extends Json
{

    public function create_from_table_screen(TableScreen $table_screen, $id): JsonResponse
    {
        $this->set_parameter('id', $id);

        if ($table_screen instanceof TableScreen\ListTable) {
            $this->set_parameter('row', $table_screen->list_table()->render_row($id));
        }

        return $this;
    }

}