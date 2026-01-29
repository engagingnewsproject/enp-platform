<?php

declare(strict_types=1);

namespace ACP\Search;

use AC;
use ACP\ListScreenPreferences;
use ACP\Search\Entity\Segment;
use ACP\Search\Type\SegmentKey;

trait DefaultSegmentTrait
{

    protected SegmentRepository $segment_repository;

    protected function get_default_segment(AC\ListScreen $list_screen): ?Segment
    {
        $segment_key = $this->get_default_segment_key($list_screen);

        if ( ! $segment_key) {
            return null;
        }

        //  test SEGMENTS
        $segments = $list_screen->get_segments();

        return $segments->contains($segment_key)
            ? $segments->get($segment_key)
            : null;
    }

    protected function get_default_segment_key(AC\ListScreen $list_screen): ?SegmentKey
    {
        $setting = $list_screen->get_preference(ListScreenPreferences::FILTER_SEGMENT);

        if ( ! $setting) {
            return null;
        }

        return new SegmentKey((string)$setting);
    }

}