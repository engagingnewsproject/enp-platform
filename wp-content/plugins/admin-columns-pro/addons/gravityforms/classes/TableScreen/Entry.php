<?php

declare(strict_types=1);

namespace ACA\GravityForms\TableScreen;

use AC;
use AC\TableScreen;
use AC\Type\Labels;
use AC\Type\TableId;
use AC\Type\Uri;
use ACA\GravityForms;
use ACA\GravityForms\ListTable;
use GF_Entry_List_Table;

class Entry extends TableScreen implements TableScreen\ListTable, TableScreen\TotalItems
{

    private int $form_id;

    public function __construct(int $form_id, Labels $labels, Uri $uri)
    {
        parent::__construct(
            new TableId('gf_entry_' . $form_id),
            '_page_gf_entries',
            $labels,
            $uri
        );

        $this->form_id = $form_id;
    }

    public function get_total_items(): int
    {
        $list_table = $this->get_list_table();
        $list_table->prepare_items();

        return (int)$list_table->get_pagination_arg('total_items');
    }

    public function get_form_id(): int
    {
        return $this->form_id;
    }

    public function get_list_table(): GF_Entry_List_Table
    {
        return (new GravityForms\TableFactory())->create($this->screen_id, $this->form_id);
    }

    public function list_table(): AC\ListTable
    {
        return new ListTable($this->get_list_table());
    }

}