<?php

declare(strict_types=1);

namespace ACP\QuickAdd\Controller;

use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\PostType;
use AC\Registerable;
use AC\Request;
use AC\Type\ListScreenId;
use ACP\QuickAdd\Model;
use RuntimeException;

class AjaxNewItem implements Registerable
{

    private $storage;

    protected $request;

    public function __construct(Storage $storage, Request $request)
    {
        $this->storage = $storage;
        $this->request = $request;
    }

    public function register(): void
    {
        if ($this->is_request()) {
            add_action('ac/table/list_screen', [$this, 'register_hooks']);
        }
    }

    public function register_hooks(ListScreen $list_screen)
    {
        $table_screen = $list_screen->get_table_screen();
        switch (true) {
            case $table_screen instanceof PostType:
                add_action('edit_posts_per_page', [$this, 'handle_request']);
                break;
        }
    }

    private function is_request(): bool
    {
        return $this->request->get('ac_action') === 'acp_add_new_inline';
    }

    public function handle_request(): void
    {
        if ( ! wp_verify_nonce($this->request->get('_ajax_nonce'), 'ac-ajax')) {
            return;
        }

        $response = new JsonResponse();

        $list_screen = $this->storage->find(new ListScreenId($this->request->get('layout')));

        if ( ! $list_screen || ! $list_screen->is_user_allowed(wp_get_current_user())) {
            $response->error();
        }

        $table_screen = $list_screen->get_table_screen();

        $model = Model\Factory::create($table_screen);

        if ( ! $model || ! $model->has_permission(wp_get_current_user())) {
            $response->error();
        }

        try {
            $id = $model->create();
        } catch (RuntimeException $e) {
            $response->set_message($e->getMessage())
                     ->error();
            exit;
        }

        do_action('ac/quick_add/saved', $id, $list_screen);

        $response->create_from_table_screen($table_screen, $id)
                 ->success();
    }

}