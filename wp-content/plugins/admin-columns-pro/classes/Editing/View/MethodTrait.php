<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

trait MethodTrait
{

    public function has_methods(bool $has_methods): View
    {
        return $this->set('has_methods', $has_methods);
    }

}