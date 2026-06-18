<?php

namespace ACP\Sorting\Controller;

use AC\Ajax;
use AC\Preferences\SiteFactory;
use AC\Registerable;
use AC\TableScreenFactory;
use AC\Type\ListScreenId;
use AC\Type\TableId;
use ACP\Sorting\UserPreference;

class AjaxResetSorting implements Registerable
{

    private TableScreenFactory $table_screen_factory;

    private SiteFactory $storage_factory;

    public function __construct(TableScreenFactory $table_screen_factory, SiteFactory $storage_factory)
    {
        $this->table_screen_factory = $table_screen_factory;
        $this->storage_factory = $storage_factory;
    }

    public function register(): void
    {
        $this->get_ajax_handler()->register();
    }

    private function get_ajax_handler(): Ajax\Handler
    {
        $handler = new Ajax\Handler();
        $handler
            ->set_action('acp_reset_sorting')
            ->set_callback([$this, 'handle_reset']);

        return $handler;
    }

    public function handle_reset()
    {
        $this->get_ajax_handler()->verify_request();

        $list_key = new TableId(filter_input(INPUT_POST, 'list_screen'));

        if ( ! $this->table_screen_factory->can_create(new TableId(filter_input(INPUT_POST, 'list_screen')))) {
            return;
        }

        $storage_key = (string)$list_key;
        $list_id = (string)filter_input(INPUT_POST, 'layout');

        if (ListScreenId::is_valid_id($list_id)) {
            $storage_key .= $list_id;
        }

        $preference = new UserPreference\SortType(
            $storage_key,
            $this->storage_factory
        );

        $preference->delete();

        wp_send_json_success();
    }

}