<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\Post;

class StatusFactory
{

    public function create(): Status
    {
        return new Status();
    }
}