<?php

namespace ACP\QuickAdd\Model;

use AC\TableScreen;

interface ModelFactory
{

    public function create(TableScreen $table_screen): ?Create;

}