<?php

declare(strict_types=1);

namespace ACP\Service;

use AC;
use AC\ListScreenRepository\Storage\ListScreenRepository;
use AC\Registerable;
use ACP\ListScreenRepository\Callback;
use ACP\ListScreenRepository\Database;
use ACP\ListScreenRepository\FileFactory;
use ACP\ListScreenRepository\Types;
use ACP\Storage\AbstractDecoderFactory;
use ACP\Storage\Directory;

final class Storage implements Registerable
{

    private AC\ListScreenRepository\Storage $storage;

    private Database $database_list_screen_repository;

    private AbstractDecoderFactory $decoder_factory;

    private FileFactory $file_factory;

    public function __construct(
        AC\ListScreenRepository\Storage $storage,
        Database $database_list_screen_repository,
        AbstractDecoderFactory $decoder_factory,
        FileFactory $file_factory
    ) {
        $this->storage = $storage;
        $this->database_list_screen_repository = $database_list_screen_repository;
        $this->decoder_factory = $decoder_factory;
        $this->file_factory = $file_factory;
    }

    public function register(): void
    {
        add_action('acp/init', [$this, 'configure'], 20);
    }

    public function configure(): void
    {
        $repositories = $this->storage->get_repositories();

        // Use the ACP version instead of the AC version
        $repositories[AC\ListScreenRepository\Types::DATABASE] = new ListScreenRepository(
            $this->database_list_screen_repository, true
        );

        $this->configure_file_storage($repositories);

        $repositories = apply_filters(
            'acp/storage/repositories',
            $repositories,
            $this->file_factory
        );

        $callbacks = apply_filters('acp/storage/repositories/callback', []);

        foreach ($callbacks as $key => $callback) {
            $repositories['ac-callback-' . $key] = new ListScreenRepository(
                new Callback($this->decoder_factory, $callback),
                false
            );
        }

        $this->storage->set_repositories($repositories);
    }

    private function configure_file_storage(array &$repositories): void
    {
        if (apply_filters('acp/storage/file/enable_for_multisite', false) && is_multisite()) {
            return;
        }

        $path = apply_filters('acp/storage/file/directory', null);

        if ( ! is_string($path) || $path === '') {
            return;
        }

        $directory = new Directory($path);

        if ( ! $directory->exists() || str_contains($path, WP_CONTENT_DIR)) {
            $directory->create();
        }

        $file = $this->file_factory->create(
            $path,
            (bool)apply_filters('acp/storage/file/directory/writable', true),
            null,
            (string)apply_filters('acp/storage/file/directory/i18n_text_domain', null)
        );

        $repositories[Types::FILE] = $file;

        if ( ! $file->is_writable() || ! $this->storage->has_repository(Types::DATABASE)) {
            return;
        }

        $repositories[AC\ListScreenRepository\Types::DATABASE] = $repositories[AC\ListScreenRepository\Types::DATABASE]->with_writable(
            false
        );
    }

}