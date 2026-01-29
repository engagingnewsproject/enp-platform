<?php

declare(strict_types=1);

namespace ACA\BP\Editing\RequestHandler\Query;

use AC\Request;
use ACP\Editing\ApplyFilter\RowsPerIteration;
use ACP\Editing\RequestHandler;
use ACP\Editing\Response;
use BP_Groups_Group;

class Groups implements RequestHandler
{

    private Request $request;

    public function handle(Request $request): void
    {
        $this->request = $request;
        add_filter('bp_groups_admin_load', [$this, 'send_editable_rows'], 10, 2);
    }

    public function send_editable_rows(): void
    {
        $ids = BP_Groups_Group::get_group_type_ids();
        $ids = $ids['all'];

        $response = new Response\QueryRows($ids, $this->get_rows_per_iteration());
        $response->success();
    }

    private function get_rows_per_iteration(): int
    {
        return (new RowsPerIteration($this->request))->apply_filters(2000);
    }

    protected function get_offset(): int
    {
        $page = (int)$this->request->filter('ac_page', 1, FILTER_SANITIZE_NUMBER_INT);

        return ($page - 1) * $this->get_rows_per_iteration();
    }

}