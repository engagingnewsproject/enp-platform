<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\ListTable\SaveHeading;

use AC\Table\SaveHeading\ScreenColumnsFactory;
use AC\TableScreen;
use ACA\BeaverBuilder\TableScreen\Template;

class TemplateFactory extends ScreenColumnsFactory
{

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof Template;
    }

}