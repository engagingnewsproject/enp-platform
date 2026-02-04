<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Capabilities;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\ListScreenId;
use ACP\Tools\FileImportHandler;

class ListScreenImportUpload implements RequestAjaxHandler
{

    use ImportMessageTrait;

    private FileImportHandler $file_import_handler;

    private AC\Nonce\Ajax $nonce;

    public function __construct(FileImportHandler $file_import_handler, AC\Nonce\Ajax $nonce)
    {
        $this->file_import_handler = $file_import_handler;
        $this->nonce = $nonce;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error(__('Invalid request.', 'codepress-admin-columns'));
        }

        $file_name = $_FILES['import']['name'] ?? null;
        $file_tmp_name = $_FILES['import']['tmp_name'] ?? null;

        if ( ! $file_name || ! $file_tmp_name) {
            wp_send_json_error(
                __('No import file was uploaded.', 'codepress-admin-columns')
            );
        }

        if ( ! ac_helper()->string->ends_with($file_name, 'json')) {
            wp_send_json_error(
                sprintf(
                    __('Uploaded file does not have a %s extension.', 'codepress-admin-columns'),
                    '.json'
                )
            );

            return;
        }

        $list_id = $request->get('list_id');

        $list_screens = $this->file_import_handler->handle(
            $file_tmp_name,
            ListScreenId::is_valid_id($list_id)
                ? new ListScreenId($list_id)
                : null
        );

        if ( ! $list_screens->count()) {
            wp_send_json_error(
                __('No (valid) column settings where found in the uploaded file.', 'codepress-admin-columns')
            );
        }

        wp_send_json_success(
            $this->create_success_message($list_screens)
        );
    }

}