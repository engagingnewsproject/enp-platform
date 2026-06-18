<?php

declare(strict_types=1);

namespace ACP\TableScreen;

use AC\Table\TableScreenCollection;
use AC\Type\TableId;

interface RelatedRepository
{

    public function find_all(TableId $table_id): TableScreenCollection;

}