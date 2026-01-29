<?php

namespace ACP\Query;

use AC\TableScreen;
use ACP\Query;

interface QueryFactory
{

    public function can_create(TableScreen $table_screen): bool;

    public function create(array $bindings): Query;

}