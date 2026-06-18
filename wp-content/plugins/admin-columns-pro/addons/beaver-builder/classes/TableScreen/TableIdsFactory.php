<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\TableScreen;

use AC;
use AC\Type\TableId;
use AC\Type\TableIdCollection;

class TableIdsFactory implements AC\TableIdsFactory
{

    public function create(): TableIdCollection
    {
        return new TableIdCollection([
            new TableId(TemplateFactory::POST_TYPE . 'layout'),
            new TableId(TemplateFactory::POST_TYPE . 'row'),
            new TableId(TemplateFactory::POST_TYPE . 'column'),
            new TableId(TemplateFactory::POST_TYPE . 'module'),
        ]);
    }

}