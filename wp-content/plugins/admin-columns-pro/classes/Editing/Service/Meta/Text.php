<?php

declare(strict_types=1);

namespace ACP\Editing\Service\Meta;

use ACP\Editing\Service;
use ACP\Editing\Storage;
use ACP\Editing\View;

class Text extends Service\BasicStorage
{

    public function __construct(string $meta_key)
    {
        parent::__construct(new Storage\Post\Meta($meta_key));
    }

    public function get_view(string $context): View
    {
        return new View\Text();
    }

}