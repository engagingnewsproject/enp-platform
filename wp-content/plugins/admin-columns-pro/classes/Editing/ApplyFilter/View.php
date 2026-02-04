<?php

namespace ACP\Editing\ApplyFilter;

use AC;
use ACP\Editing;
use ACP\Editing\Service;

class View
{

    private AC\Column\Context $context;

    private string $edit_context;

    private Service $service;

    private AC\TableScreen $table_screen;

    public function __construct(
        AC\Column\Context $context,
        string $edit_context,
        Service $service,
        AC\TableScreen $table_screen
    ) {
        $this->context = $context;
        $this->edit_context = $edit_context;
        $this->service = $service;
        $this->table_screen = $table_screen;
    }

    public function apply_filters(?Editing\View $view = null): ?Editing\View
    {
        $view = apply_filters(
            'ac/editing/view',
            $view,
            $this->context,
            $this->edit_context,
            $this->service,
            $this->table_screen
        );

        return $view instanceof Editing\View
            ? $view
            : null;
    }

}