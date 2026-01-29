<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\SaveHeadings;

use AC\Registerable;
use AC\Storage\Repository\OriginalColumnsRepository;
use AC\Type\OriginalColumns;
use AC\Type\TableId;

class ScreenColumns implements Registerable
{

    private OriginalColumnsRepository $repository;

    private TableId $table_id;

    private bool $do_exit;

    private string $prefix;

    public function __construct(
        OriginalColumnsRepository $repository,
        TableId $table_id,
        string $prefix,
        bool $do_exit = true
    ) {
        $this->repository = $repository;
        $this->table_id = $table_id;
        $this->do_exit = $do_exit;
        $this->prefix = $prefix;
    }

    public function register(): void
    {
        add_filter($this->prefix . 'list_table_get_columns', [$this, 'handle'], 199);
        add_filter($this->prefix . 'list_table_get_sortable_columns', [$this, 'handle_sortable'], 199);
    }

    public function handle(array $headings): void
    {
        remove_filter($this->prefix . 'list_table_get_columns', [$this, 'handle'], 199);

        if ( ! empty($headings)) {
            $this->repository->update(
                $this->table_id,
                OriginalColumns::create_from_headings($headings)
            );
        }
    }

    public function handle_sortable(array $sortable_columns): void
    {
        remove_filter($this->prefix . 'list_table_get_sortable_columns', [$this, 'handle_sortable'], 199);

        $columns = $this->repository->find_all($this->table_id);

        $sortables = array_keys($sortable_columns);

        foreach ($columns as $column) {
            $column->set_sortable(
                in_array($column->get_name(), $sortables, true)
            );
        }

        $this->repository->update(
            $this->table_id,
            $columns
        );

        if ($this->do_exit) {
            ob_clean();
            exit('ac_success');
        }
    }

}