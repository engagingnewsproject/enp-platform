<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

interface MaxLength
{

    public function set_max_length(int $max_length): View;

}