<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Capabilities;
use AC\ListScreenCollection;
use AC\ListScreenRepository\Storage;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\ListScreenId;
use AC\Type\ListScreenStatus;
use ACP\ListScreenFactory;
use ACP\ListScreenRepository\TemplateJsonFile;

class ListScreenImportTemplate implements RequestAjaxHandler
{

    use ImportMessageTrait;

    private AC\Nonce\Ajax $nonce;

    private Storage $storage;

    private TemplateJsonFile $template_repository;

    private ListScreenFactory $list_screen_factory;

    public function __construct(
        AC\Nonce\Ajax $nonce,
        TemplateJsonFile $template_repository,
        Storage $storage,
        ListScreenFactory $list_screen_factory
    ) {
        $this->nonce = $nonce;
        $this->storage = $storage;
        $this->template_repository = $template_repository;
        $this->list_screen_factory = $list_screen_factory;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        $template = $this->template_repository->find(
            new ListScreenId($request->get('list_id'))
        );

        if ( ! $template) {
            wp_send_json_error('Listscreen template not found');
        }

        $list_screen = $this->list_screen_factory->duplicate($template);
        $list_screen->set_status(ListScreenStatus::create_active());

        $this->storage->save($list_screen);

        wp_send_json_success(
            $this->create_success_message(new ListScreenCollection([$list_screen]))
        );
    }

}