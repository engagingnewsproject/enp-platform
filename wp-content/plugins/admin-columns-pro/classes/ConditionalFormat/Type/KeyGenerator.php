<?php

declare(strict_types=1);

namespace ACP\ConditionalFormat\Type;

use AC;

final class KeyGenerator extends AC\Type\KeyGenerator
{

    public function generate(): Key
    {
        return new Key($this->generate_raw());
    }

}