<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\ListTable\ManageValue;

use AC\TableScreen;
use ACA\BeaverBuilder\TableScreen\Template;

class TemplateFactory extends TableScreen\ManageValue\PostServiceFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof Template;
    }

}