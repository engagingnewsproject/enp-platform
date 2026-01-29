<?php

declare(strict_types=1);

namespace ACA\GravityForms\ListTable\SaveHeading;

use AC\Registerable;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\Table\SaveHeadingFactory;
use AC\TableScreen;
use ACA\GravityForms\TableScreen\Entry;

class EntryFactory implements SaveHeadingFactory
{

    private OriginalColumnsRepository $repository;

    public function __construct(OriginalColumnsRepository $repository)
    {
        $this->repository = $repository;
    }

    public function can_create(TableScreen $table_screen): bool
    {
        return $table_screen instanceof Entry;
    }

    public function create(TableScreen $table_screen): ?Registerable
    {
        return new ScreenColumns(
            $table_screen->get_id(),
            $this->repository
        );
    }

}