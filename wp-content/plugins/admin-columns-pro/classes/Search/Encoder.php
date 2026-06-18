<?php

declare(strict_types=1);

namespace ACP\Search;

use ACP\Search\Entity\Segment;

final class Encoder
{

    public function encode(SegmentCollection $segments): array
    {
        $encoded = [];

        foreach ($segments as $segment) {
            $encoded[] = $this->encode_segment($segment);
        }

        return $encoded;
    }

    public function encode_segment(Segment $segment): array
    {
        return [
            SegmentSchema::KEY            => (string)$segment->get_key(),
            SegmentSchema::LIST_SCREEN_ID => (string)$segment->get_list_id(),
            SegmentSchema::NAME           => $segment->get_name(),
            SegmentSchema::URL_PARAMETERS => $segment->get_url_parameters(),
            SegmentSchema::DATE_CREATED   => $segment->get_modified()->format('U'),
        ];
    }

}