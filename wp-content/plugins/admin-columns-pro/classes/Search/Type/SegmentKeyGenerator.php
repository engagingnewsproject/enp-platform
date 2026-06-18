<?php

declare(strict_types=1);

namespace ACP\Search\Type;

use AC\Type\KeyGenerator;

final class SegmentKeyGenerator extends KeyGenerator
{

    public function generate(): SegmentKey
    {
        return new SegmentKey($this->generate_raw());
    }

}