<?php

declare(strict_types=1);

namespace ACP\ListScreenRepository;

use AC\ListScreen;
use ACP\Exception\FailedToSaveSegmentException;
use ACP\Search\SegmentRepositoryWritable;

final class SegmentHandler
{

    private SegmentRepositoryWritable $repository;

    public function __construct(
        SegmentRepositoryWritable $repository
    ) {
        $this->repository = $repository;
    }

    public function load(ListScreen $list_screen): void
    {
        $list_screen->set_segments(
            $this->repository->find_all_shared($list_screen->get_id())
        );
    }

    /**
     * @throws FailedToSaveSegmentException
     */
    public function save(ListScreen $list_screen): void
    {
        $segments = $list_screen->get_segments();

        if ( ! $segments->count()) {
            return;
        }

        $saved_segments = $this->repository->find_all_shared($list_screen->get_id());

        foreach ($segments as $segment) {
            if ( ! $saved_segments->contains($segment->get_key())) {
                $this->repository->save($segment);
            }
        }

        foreach ($saved_segments as $segment) {
            if ( ! $segments->contains($segment->get_key())) {
                $this->repository->delete($segment->get_key());
            }
        }
    }

    public function delete(ListScreen $list_screen): void
    {
        $this->repository->delete_all($list_screen->get_id());
    }

}