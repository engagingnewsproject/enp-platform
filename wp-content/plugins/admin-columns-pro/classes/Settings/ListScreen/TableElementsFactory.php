<?php

declare(strict_types=1);

namespace ACP\Settings\ListScreen;

use AC\PostType;
use AC\TableScreen;
use AC\TableScreen\Comment;
use AC\TableScreen\User;

class TableElementsFactory
{

    public function create(TableScreen $table_screen): TableElements
    {
        $elements = new TableElements();

        $elements->add(new TableElement\Filters(), 30)
                 ->add(new TableElement\Search(), 90)
                 ->add(new TableElement\BulkActions(), 100)
                 ->add(new TableElement\ColumnResize(), 110)
                 ->add(new TableElement\ColumnOrder(), 120)
                 ->add(new TableElement\RowActions(), 130);

        switch (true) {
            case $table_screen instanceof PostType :
                $post_type = $table_screen->get_post_type();

                $elements->add(new TableElement\FilterPostDate(), 32);

                // Exclude Media, but make sure to include all other post types
                if ( ! $post_type->equals('attachment')) {
                    $elements->add(new TableElement\SubMenu\PostStatus(), 80);
                }

                if ($post_type->equals('attachment')) {
                    $elements->add(new TableElement\FilterMediaItem(), 31);
                }

                if (is_object_in_taxonomy((string)$post_type, 'category')) {
                    $elements->add(new TableElement\FilterCategory(), 34);
                }

                if (post_type_supports((string)$post_type, 'post-formats')) {
                    $elements->add(new TableElement\FilterPostFormat(), 36);
                }

                break;
            case $table_screen instanceof User:
                $elements->add(new TableElement\SubMenu\Roles(), 80);

                break;
            case $table_screen instanceof Comment:
                $elements->add(new TableElement\FilterCommentType(), 31);
                $elements->add(new TableElement\SubMenu\CommentStatus(), 80);

                break;
        }

        do_action('ac/admin/settings/table_elements', $elements, $table_screen);

        return $elements;
    }

}