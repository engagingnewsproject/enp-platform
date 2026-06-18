<?php

namespace ACP\Table\HideElement;

use AC\TableScreen;
use ACP\Table\HideElement;

class Search implements HideElement
{

    private TableScreen $table_screen;

    public function __construct(TableScreen $table_screen)
    {
        $this->table_screen = $table_screen;
    }

    public function hide(): void
    {
        add_action('ac/admin_head', [$this, 'render']);
    }

    public function render(): void
    {
        ?>
		<style>
			<?= sprintf( '%s { display: none; }', $this->get_search_selector() ); ?>
		</style>
        <?php
    }

    private function get_search_selector(): string
    {
        switch (true) {
            case $this->table_screen instanceof TableScreen\Media :
                return '.wrap form#posts-filter div.search-form';
            case $this->table_screen instanceof TableScreen\Post :
                return '.wrap form#posts-filter p.search-box';
            default :
                return 'p.search-box';
        }
    }

}