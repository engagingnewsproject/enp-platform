<?php

namespace ACP\Table;

use AC\ListScreen;
use AC\Registerable;
use ACP\Search;
use ACP\Settings\ListScreen\TableElement;
use ACP\Sorting\BulkActions;

final class HideElements implements Registerable
{

    public function register(): void
    {
        add_action('ac/table_scripts', [$this, 'hide_elements']);
    }

    public function hide_elements(ListScreen $list_screen)
    {
        $hidden_elements = [];

        if ( ! (new TableElement\FilterMediaItem())->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\FilterMediaItems();
        }

        if ( ! (new TableElement\FilterPostDate())->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\FilterPostDate();
        }

        if ( ! (new TableElement\FilterPostFormat())->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\FilterPostFormats();
        }

        if ( ! (new TableElement\FilterCategory())->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\FilterPostCategories();
        }

        if ( ! (new TableElement\FilterCommentType())->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\FilterCommentTypes();
        }

        if ( ! (new TableElement\Search())->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\Search($list_screen->get_table_screen());
        }

        if ( ! (new TableElement\BulkActions())->is_enabled($list_screen)) {
            $hidden_elements[] = new BulkActions();
        }

        if ( ! (new TableElement\RowActions())->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\RowActions($list_screen->get_table_screen());
        }

        if ( ! (new TableElement\SubMenu(''))->is_enabled($list_screen)) {
            $hidden_elements[] = new HideElement\SubMenu();
        }

        $filters_enabled = (new TableElement\Filters())->is_enabled($list_screen);

        if ( ! $filters_enabled) {
            $hidden_elements[] = new HideElement\Filters();
        }

        $smart_filters_enabled = (new Search\Settings\TableElement\SmartFilters())->is_enabled($list_screen);

        if ( ! $smart_filters_enabled && ! $filters_enabled) {
            $hidden_elements[] = new HideElement\ActionsBar();
        }

        foreach ($hidden_elements as $hidden_element) {
            $hidden_element->hide();
        }
    }

}