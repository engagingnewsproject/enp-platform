<?php

declare(strict_types=1);

namespace ACP\Plugin\Update\V7000;

use AC\ListScreenRepository\Storage;
use ACP\Exception\FileNotWritableException;
use ACP\ListScreenRepository\SourceAware;
use SplFileInfo;

/**
 * Reads, writes and parses files in the given directory, looking for valid files with the expected structure.
 * Only files that are between versions 6.3 and 7.0 are compatible.
 */
class FileRepository
{

    private Storage $storage;

    public function __construct(Storage $storage)
    {
        $this->storage = $storage;
    }

    public function find_all(string $from_version, string $to_version): array
    {
        $files = [];

        foreach ($this->storage->get_repositories() as $repository) {
            $repo = $repository->get_list_screen_repository();

            if ( ! $repo instanceof SourceAware) {
                continue;
            }

            foreach ($repo->get_sources() as $source) {
                $file = new SplFileInfo($source);

                if ( ! $this->is_valid_file($file)) {
                    continue;
                }

                $data = require $file->getRealPath();

                $version = $data['version'] ?? '';

                if ( ! $version) {
                    continue;
                }

                if (version_compare($version, $from_version, '<')) {
                    continue;
                }

                if (version_compare($version, $to_version, '>=')) {
                    continue;
                }

                $files[(string)$file->getRealPath()] = $data;
            }
        }

        return $files;
    }

    private function is_valid_file(SplFileInfo $file): bool
    {
        return $file->isFile() && $file->isWritable() && $file->getExtension() === 'php';
    }

    /**
     * $files array must contain writable file paths as keys and associative arrays with the column configs as values.
     */
    public function save(string $file_path, array $data): void
    {
        $file = new SplFileInfo($file_path);

        if ( ! $this->is_valid_file($file)) {
            throw new FileNotWritableException();
        }

        $contents = '<?php' . "\n\n" . 'return ' . var_export($data, true) . ';';

        $result = file_put_contents($file->getRealPath(), $contents);

        if ($result === false) {
            throw new FileNotWritableException();
        }

        if (function_exists('opcache_invalidate')) {
            opcache_invalidate($file_path);
        }
    }

}