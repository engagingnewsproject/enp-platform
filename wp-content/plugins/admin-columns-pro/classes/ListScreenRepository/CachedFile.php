<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use AC\ListScreenCollection;
use AC\ListScreenRepository\ListScreenRepositoryTrait;
use AC\ListScreenRepositoryWritable;
use ACP\Exception\DirectoryNotWritableException;
use ACP\Exception\FailedToCreateDirectoryException;
use ACP\Exception\FailedToSaveSegmentException;
use ACP\Exception\FileNotWritableException;
use ACP\Storage\Directory;

final class CachedFile implements ListScreenRepositoryWritable, SourceAware, DirectoryAware
{

    use ListScreenRepositoryTrait;
    use FilteredListScreenRepositoryTrait;

    private File $list_screen_repository;

    private ?SourceCollection $sources = null;

    private ?ListScreenCollection $list_screens = null;

    public function __construct(
        File $list_screen_repository
    ) {
        $this->list_screen_repository = $list_screen_repository;
    }

    protected function find_all_from_source(): ListScreenCollection
    {
        if (null === $this->list_screens) {
            $this->list_screens = $this->list_screen_repository->find_all();
        }

        return $this->list_screens;
    }

    public function get_directory(): Directory
    {
        return $this->list_screen_repository->get_directory();
    }

    /**
     * @throws FileNotWritableException
     * @throws DirectoryNotWritableException
     * @throws FailedToSaveSegmentException
     * @throws FailedToCreateDirectoryException
     */
    public function save(ListScreen $list_screen): void
    {
        $this->list_screen_repository->save($list_screen);

        $this->flush();
    }

    /**
     * @throws FileNotWritableException
     */
    public function delete(ListScreen $list_screen): void
    {
        $this->list_screen_repository->delete($list_screen);

        $this->flush();
    }

    public function get_sources(): SourceCollection
    {
        if (null === $this->sources) {
            $this->sources = $this->list_screen_repository->get_sources();
        }

        return $this->sources;
    }

    private function flush(): void
    {
        $this->list_screens = null;
        $this->sources = null;
    }

}