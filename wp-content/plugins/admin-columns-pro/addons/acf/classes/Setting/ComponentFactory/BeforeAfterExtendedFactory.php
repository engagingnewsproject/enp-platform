<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

class BeforeAfterExtendedFactory
{

    public function create(string $before, string $after): BeforeAfterExtended
    {
        return new BeforeAfterExtended(
            $before,
            $after
        );
    }

}