<?php

declare(strict_types=1);

namespace ACP\Service;

use AC;
use AC\ListScreenRepositoryWritable;
use AC\Registerable;
use ACP\ListScreenRepository\Types;
use http\Exception\RuntimeException;

class FileStorageMigrationHandler implements Registerable
{

    private AC\ListScreenRepository\Storage $storage;

    public function __construct(AC\ListScreenRepository\Storage $storage)
    {
        $this->storage = $storage;
    }

    public function register(): void
    {
        // Migrate can only run after post-types have been initialized
        add_action('init', [$this, 'handle'], 300);
    }

    public function run_migration_to_database(): void
    {
        $file = $this->get_files_repository();
        $database = $this->get_database_repository();

        foreach ($file->find_all() as $list_screen) {
            $file->delete($list_screen);
            $database->save($list_screen);
        }
    }

    public function run_migration_to_files(): void
    {
        $file = $this->get_files_repository();
        $database = $this->get_database_repository();

        foreach ($database->find_all() as $list_screen) {
            $file->save($list_screen);
            $database->delete($list_screen);
        }
    }

    private function get_files_repository(): ListScreenRepositoryWritable
    {
        if ( ! $this->storage->has_repository(Types::FILE)) {
            throw new RuntimeException('File repository not found.');
        }

        $file = $this->storage
            ->get_repository(Types::FILE)
            ->get_list_screen_repository();

        if ( ! $file instanceof ListScreenRepositoryWritable) {
            throw new RuntimeException('File storage repository is not writable.');
        }

        return $file;
    }

    private function get_database_repository(): ListScreenRepositoryWritable
    {
        if ( ! $this->storage->has_repository(AC\ListScreenRepository\Types::DATABASE)) {
            throw new RuntimeException('Database repository not found.');
        }

        $database = $this->storage
            ->get_repository(AC\ListScreenRepository\Types::DATABASE)
            ->with_writable(true)
            ->get_list_screen_repository();

        if ( ! $database instanceof ListScreenRepositoryWritable) {
            throw new RuntimeException('Database repository is not writable.');
        }

        return $database;
    }

    public function handle(): void
    {
        $do_migrate = apply_filters('acp/storage/file/directory/migrate', false);

        if ($do_migrate) {
            $this->run_migration_to_files();
        }
    }

}