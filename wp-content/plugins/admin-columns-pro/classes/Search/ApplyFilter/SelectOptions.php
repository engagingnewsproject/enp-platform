<?php

namespace ACP\Search\ApplyFilter;

use AC\Column\Context;
use AC\ListScreen;

class SelectOptions
{

    private ListScreen $list_screen;

    private Context $context;

    public function __construct(Context $context, ListScreen $list_screen)
    {
        $this->context = $context;
        $this->list_screen = $list_screen;
    }

    public function apply_filters(array $options): array
    {
        return (array)apply_filters('ac/search/select/options', $options, $this->context, $this->list_screen);
    }

}