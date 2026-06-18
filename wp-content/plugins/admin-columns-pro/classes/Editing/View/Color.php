<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

class Color extends View
{

    public function __construct()
    {
        parent::__construct('color');
    }

    public function set_palletes(array $palletes): self
    {
        return $this->set('palettes', $palletes);
    }

}