<?php

declare(strict_types=1);

namespace ACP;

use AC\Collection;

final class AddonCollection extends Collection
{

    public function add(Addon $addon): void
    {
        $this->data[] = $addon;
    }

    public function current(): Addon
    {
        return current($this->data);
    }

}
