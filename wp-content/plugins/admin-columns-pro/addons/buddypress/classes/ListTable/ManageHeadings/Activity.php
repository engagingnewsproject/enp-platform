<?php

declare(strict_types=1);

namespace ACA\BP\ListTable\ManageHeadings;

use AC\Registerable;

class Activity implements Registerable
{

    private array $headings;

    public function __construct(array $headings)
    {
        $this->headings = $headings;
    }

    public function register(): void
    {
        add_filter('bp_activity_list_table_get_columns', [$this, 'handle'], 200);
    }

    public function handle($current_headings): array
    {
        $headings = $this->headings;
        $checkbox = $current_headings['cb'] ?? null;

        if ($checkbox) {
            $headings = ['cb' => $checkbox] + $headings;
        }

        return $headings;
    }

}