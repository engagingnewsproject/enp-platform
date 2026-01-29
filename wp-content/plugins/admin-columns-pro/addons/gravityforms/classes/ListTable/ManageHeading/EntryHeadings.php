<?php

declare(strict_types=1);

namespace ACA\GravityForms\ListTable\ManageHeading;

use AC\Registerable;

class EntryHeadings implements Registerable
{

    /**
     * @var array [ $column_id => $label, ... ]
     */
    private array $headings;

    public function __construct(array $headings)
    {
        $this->headings = $headings;
    }

    public function register(): void
    {
        add_filter('gform_entry_list_columns', [$this, 'handle'], 200);
    }

    public function handle(array $current_headings): array
    {
        $headings = $this->headings;
        $checkbox = $current_headings['cb'] ?? null;

        if ($checkbox) {
            $headings = ['cb' => $checkbox] + $headings;
        }

        return $headings;
    }

}