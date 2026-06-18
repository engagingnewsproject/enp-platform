<?php

namespace ACP\Editing\ApplyFilter;

use AC\Request;

class RowsPerIteration
{

    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function apply_filters(int $rows_per_iteration): int
    {
        return (int)apply_filters('acp/editing/rows_per_iteration', $rows_per_iteration, $this->request);
    }

}