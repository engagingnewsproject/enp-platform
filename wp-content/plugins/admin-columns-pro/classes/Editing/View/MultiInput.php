<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

class MultiInput extends View
{

    public function __construct()
    {
        parent::__construct('multi_input');
    }

    public function set_sub_type(string $sub_type): self
    {
        return $this->set('subtype', $sub_type);
    }

}