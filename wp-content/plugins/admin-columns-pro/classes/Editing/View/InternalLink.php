<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

class InternalLink extends View implements Placeholder, MaxLength
{

    use MaxlengthTrait;
    use PlaceholderTrait;

    public function __construct()
    {
        parent::__construct('internal_link');
    }

}