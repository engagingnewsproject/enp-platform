<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

trait MaxlengthTrait
{

    public function set_max_length(int $max_length): View
    {
        return $this->set('maxlength', $max_length);
    }

}