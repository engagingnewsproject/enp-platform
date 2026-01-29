<?php

namespace ACP\Table\HideElement;

use AC;
use ACP\Table\HideElement;
use ACP\TableScreen;

class RowActions implements HideElement
{

    private $table_screen;

    public function __construct(AC\TableScreen $table_screen)
    {
        $this->table_screen = $table_screen;
    }

    public function hide(): void
    {
        $table_screen = $this->table_screen;

        switch (true) {
            case $table_screen instanceof AC\TableScreen\Post :
                if (is_post_type_hierarchical((string)$table_screen->get_post_type())) {
                    add_filter('page_row_actions', '__return_empty_array', 10000);

                    break;
                }
                add_filter('post_row_actions', '__return_empty_array', 10000);

                break;
            case $table_screen instanceof AC\TableScreen\Media :
                add_filter('media_row_actions', '__return_empty_array', 10000);

                break;
            case $table_screen instanceof TableScreen\NetworkUser :
                add_filter('ms_user_row_actions', '__return_empty_array', 10000);

                break;
            case $table_screen instanceof AC\TableScreen\User :
                add_filter('user_row_actions', '__return_empty_array', 10000);

                break;
            case $table_screen instanceof TableScreen\Taxonomy :
                add_filter($table_screen->get_taxonomy() . "_row_actions", '__return_empty_array', 10000);

                break;
            case $table_screen instanceof AC\TableScreen\Comment :
                add_filter('comment_row_actions', '__return_empty_array', 10000);

                break;
        }

        add_action('ac/admin_head', function () {
            ?>
			<style>
				.wp-list-table .row-actions {
					display: none !important;
				}
			</style>
            <?php
        });
    }

}