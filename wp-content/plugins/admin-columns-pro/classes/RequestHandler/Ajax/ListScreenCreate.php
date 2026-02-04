<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Capabilities;
use AC\ListScreen;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Response;
use AC\Storage\Repository\ListScreenOrder;
use AC\TableScreenFactory;
use AC\Type\ListScreenId;
use AC\Type\ListScreenIdGenerator;
use AC\Type\ListScreenStatus;
use AC\Type\TableId;
use ACP\Admin\Encoder;
use ACP\ListScreenFactory;
use ACP\ListScreenRepository\TemplateJsonFile;
use RuntimeException;

final class ListScreenCreate implements RequestAjaxHandler
{

    private Storage $storage;

    private ListScreenOrder $order_storage;

    private TableScreenFactory $table_screen_factory;

    private AC\Nonce\Ajax $nonce;

    private TemplateJsonFile $template_storage;

    private AC\ColumnTypeRepository $type_repository;

    private ListScreenFactory $list_screen_factory;

    private ListScreenIdGenerator $list_screen_id_generator;

    public function __construct(
        Storage $storage,
        TableScreenFactory $table_screen_factory,
        ListScreenOrder $order_storage,
        AC\Nonce\Ajax $nonce,
        TemplateJsonFile $template_storage,
        AC\ColumnTypeRepository $type_repository,
        ListScreenFactory $list_screen_factory,
        ListScreenIdGenerator $list_screen_id_generator
    ) {
        $this->storage = $storage;
        $this->table_screen_factory = $table_screen_factory;
        $this->order_storage = $order_storage;
        $this->nonce = $nonce;
        $this->template_storage = $template_storage;
        $this->type_repository = $type_repository;
        $this->list_screen_factory = $list_screen_factory;
        $this->list_screen_id_generator = $list_screen_id_generator;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();
        $response = new Response\Json();

        if ( ! $this->nonce->verify($request)) {
            $response->error();
        }

        $list_key = new TableId($request->get('list_key'));

        if ( ! $this->table_screen_factory->can_create($list_key)) {
            return;
        }

        $table_screen = $this->table_screen_factory->create($list_key);

        $title = trim($request->get('title'));

        if ( ! $title) {
            $response->set_message(__('Name can not be empty.', 'codepress-admin-columns'))
                     ->error();
        }

        $copy_list_id = $request->get('list_id');

        if (ListScreenId::is_valid_id($copy_list_id)) {
            $copy_list_id = new ListScreenId($copy_list_id);

            $list_screen_source = $this->storage->find($copy_list_id);

            if ( ! $list_screen_source) {
                $list_screen_source = $this->template_storage->find($copy_list_id);
            }

            if ( ! $list_screen_source) {
                $response
                    ->set_message('Invalid list screen source.')
                    ->error();
            }

            $list_screen = $this->list_screen_factory->duplicate($list_screen_source);
            $list_screen->set_title($title);
            $list_screen->set_table_screen($table_screen);
            $list_screen->set_status(ListScreenStatus::create_active());
        } else {
            $list_screen = new ListScreen(
                $this->list_screen_id_generator->generate(),
                $title,
                $table_screen,
                $this->type_repository->find_all_by_original($table_screen)
            );
        }

        do_action('ac/list_screen/before_create', $list_screen);

        try {
            $this->storage->save($list_screen);
        } catch (RuntimeException $e) {
            $response
                ->set_message($e->getMessage())
                ->error();
        }

        do_action('ac/list_screen/created', $list_screen);

        $this->order_storage->add(
            $list_screen->get_table_id(),
            $list_screen->get_id()
        );

        $response->set_parameters(
            (new Encoder($list_screen))->encode()
        )->success();
    }

}