<?php

declare(strict_types=1);

namespace ACP\Editing\BulkDelete;

use AC\TableScreen;

interface StrategyFactory
{

    public function create(TableScreen $table_screen): ?Deletable;

}