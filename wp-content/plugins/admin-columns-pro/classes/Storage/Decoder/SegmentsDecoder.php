<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use ACP\Search\SegmentCollection;

interface SegmentsDecoder
{

    public function has_segments(): bool;

    public function get_segments(): SegmentCollection;

}