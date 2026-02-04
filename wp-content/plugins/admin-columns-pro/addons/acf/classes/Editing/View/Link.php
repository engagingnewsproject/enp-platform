<?php

declare(strict_types=1);

namespace ACA\ACF\Editing\View;

use ACA;
use ACP\Editing\View;

class Link extends View
{

    public function __construct()
    {
        parent::__construct('acf_link');
    }
}