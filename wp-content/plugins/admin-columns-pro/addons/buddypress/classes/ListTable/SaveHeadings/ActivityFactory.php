<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\SaveHeadings;

use AC\Registerable;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\Table\SaveHeadingFactory;
use AC\TableScreen;
use ACA\BP;

class ActivityFactory implements SaveHeadingFactory
{

    private OriginalColumnsRepository $repository;

    public function __construct(OriginalColumnsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof BP\TableScreen\Activity;
    }

    public function create(TableScreen $table_screen): ?Registerable
    {
        return new ScreenColumns($this->repository, $table_screen->get_id(), 'bp_activity_');
    }
}