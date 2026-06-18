<?php

declare(strict_types=1);

namespace ACA\GravityForms\ListTable\SaveHeading;

use AC\Registerable;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\Type\OriginalColumns;
use AC\Type\TableId;

class ScreenColumns implements Registerable
{

    private OriginalColumnsRepository $repository;

    private TableId $table_id;

    private bool $do_exit;

    public function __construct(TableId $table_id, OriginalColumnsRepository $repository, bool $do_exit = true)
    {
        $this->repository = $repository;
        $this->table_id = $table_id;
        $this->do_exit = $do_exit;
    }

    public function register(): void
    {
        add_filter('gform_entry_list_columns', [$this, 'handle'], 200);
    }

    public function handle(array $headings): void
    {
        if ( ! empty($headings)) {
            $this->repository->update(
                $this->table_id,
                OriginalColumns::create_from_headings($headings)
            );
        }

        if ($this->do_exit) {
            ob_clean();
            exit('ac_success');
        }
    }

}