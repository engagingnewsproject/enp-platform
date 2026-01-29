<?php

namespace ACP\Export\RequestHandler\Ajax;

use AC\ListScreenRepository\Storage;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\ListScreenId;

final class FileName implements RequestAjaxHandler
{

    private $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function handle(): void
    {
        $request = new Request();

        if ( ! (new Nonce\Ajax())->verify($request)) {
            wp_send_json_error();
        }

        $id = (string)$request->filter('layout', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        if ( ! ListScreenId::is_valid_id($id)) {
            wp_send_json_error();
        }

        $list_screen = $this->storage->find(
            new ListScreenId($id)
        );

        if ( ! $list_screen) {
            wp_send_json_error();
        }

        // This hook allows you to change the default generated CSV filename.
        $file_name = apply_filters(
            'ac/export/file_name',
            (string)$request->filter('file_name', null, FILTER_SANITIZE_FULL_SPECIAL_CHARS),
            $list_screen
        );

        wp_send_json_success((string)$file_name);
    }

}