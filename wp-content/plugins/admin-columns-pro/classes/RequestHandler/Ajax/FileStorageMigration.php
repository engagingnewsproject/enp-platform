<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\Service\FileStorageMigrationHandler;
use RuntimeException;

class FileStorageMigration implements RequestAjaxHandler
{

    private Nonce\Ajax $nonce;

    private FileStorageMigrationHandler $handler;

    public function __construct(Nonce\Ajax $nonce, FileStorageMigrationHandler $handler)
    {
        $this->nonce = $nonce;
        $this->handler = $handler;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error(__('Invalid request', 'codepress-admin-columns'));
        }

        try {
            'migrate_to_database' === $request->get('action', 'migrate_to_files')
                ? $this->handler->run_migration_to_database()
                : $this->handler->run_migration_to_files();
        } catch (RuntimeException $e) {
            wp_send_json_error($e->getMessage());
        }

        wp_send_json_success([
            'message' => __('Migration completed successfully.', 'codepress-admin-columns'),
        ]);
    }

}