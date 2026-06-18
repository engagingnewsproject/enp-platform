<?php

declare(strict_types=1);

namespace ACP\RequestHandler\Ajax;

use AC;
use AC\Capabilities;
use AC\ListScreenRepository\Storage;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\ListScreenRepository\DirectoryAware;
use ACP\ListScreenRepository\SourceAware;
use ACP\ListScreenRepository\Types;
use ACP\Storage\Directory;

class FileStorageSettings implements RequestAjaxHandler
{

    private Nonce\Ajax $nonce;

    private Storage $storage;

    public function __construct(Nonce\Ajax $nonce, Storage $storage)
    {
        $this->nonce = $nonce;
        $this->storage = $storage;
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

        $file_repository = $this->get_file_repository();
        $directory = $file_repository
            ? $this->get_directory($file_repository)
            : null;
        $enabled = null !== $directory;
        $is_writable = $file_repository && $file_repository->is_writable();

        wp_send_json_success([
            'enabled'           => $enabled,
            'error'             => $enabled && ! $is_writable
                ? __('The directory is not writable.', 'codepress-admin-columns')
                : null,
            'path'              => $directory
                ? $this->create_relative_path($directory)
                : null,
            'file_repositories' => $this->get_file_repositories() ?: [],
            'files'             => $file_repository
                ? $this->get_files($file_repository->get_list_screen_repository())
                : [],
            'migration_items'   => $is_writable && $directory && $this->can_migrate()
                ? $this->get_migration_items($directory->get_path())
                : [],
        ]);
    }

    private function create_relative_path(Directory $directory): string
    {
        if ( ! str_contains($directory->get_path(), ABSPATH)) {
            return $directory->get_path();
        }

        return '../' . str_replace(ABSPATH, '', $directory->get_path());
    }

    private function get_migration_items(string $directory): array
    {
        $repo = $this->get_database_repository();

        if ( ! $repo) {
            return [];
        }

        $items = [];

        foreach ($repo->get_list_screen_repository()->find_all() as $list_screen) {
            $id = (string)$list_screen->get_id();
            $table = $list_screen->get_table_screen()->get_labels()->get_singular();

            $items[] = [
                'id'          => $id,
                'label'       => $list_screen->get_title() ?: $table,
                'url'         => (string)$list_screen->get_table_url(),
                'destination' => sprintf('../%s/%s.php', basename($directory), $id),
                'table'       => $table,
            ];
        }

        return $items;
    }

    private function get_database_repository(): ?AC\ListScreenRepository\Storage\ListScreenRepository
    {
        return $this->storage->has_repository(AC\ListScreenRepository\Types::DATABASE)
            ? $this->storage->get_repository(AC\ListScreenRepository\Types::DATABASE)
            : null;
    }

    private function get_directory(AC\ListScreenRepository\Storage\ListScreenRepository $repo): ?Directory
    {
        $file = $repo->get_list_screen_repository();

        return $file instanceof DirectoryAware
            ? $file->get_directory()
            : null;
    }

    private function get_file_repositories(): array
    {
        $repositories = [];

        foreach ($this->storage->get_repositories() as $id => $repository) {
            if (Types::FILE === $id) {
                continue;
            }

            $repo = $repository->get_list_screen_repository();

            if ( ! $repo instanceof DirectoryAware) {
                continue;
            }

            $repositories[] = [
                'id'       => $id,
                'files'    => $this->get_files($repo),
                'path'     => $this->create_relative_path($repo->get_directory()),
                'writable' => $repository->is_writable(),
            ];
        }

        return $repositories;
    }

    private function get_file_repository(): ?AC\ListScreenRepository\Storage\ListScreenRepository
    {
        return $this->storage->has_repository(Types::FILE)
            ? $this->storage->get_repository(Types::FILE)
            : null;
    }

    private function can_migrate(): bool
    {
        $file = $this->get_file_repository();

        return $this->get_database_repository() && $file && $file->is_writable();
    }

    private function get_files(AC\ListScreenRepository $repository): ?array
    {
        if ( ! $repository instanceof SourceAware) {
            return null;
        }

        $files = [];

        foreach ($repository->get_sources() as $source) {
            $files[] = (string)$source;
        }

        return $files;
    }

}