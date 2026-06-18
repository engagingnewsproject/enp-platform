<?php

declare(strict_types=1);

namespace ACP\TableScreen\RelatedRepository;

use AC;
use AC\Type\TableId;
use AC\Type\TableIdCollection;
use ACP\TableScreen\RelatedRepository;

class User implements RelatedRepository
{

    use TableTrait;

    public function __construct(AC\TableScreenFactory\Aggregate $table_screen_factory)
    {
        $this->set_table_screen_factory($table_screen_factory);
    }

    public function get_keys(): TableIdCollection
    {
        return new TableIdCollection([
            new TableId('wp-user'),
            new TableId('wp-ms_users'),
        ]);
    }

}