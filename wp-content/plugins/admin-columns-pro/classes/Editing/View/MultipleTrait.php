<?php

namespace ACP\Editing\View;

trait MultipleTrait
{

    /**
     * @return static
     */
    public function set_multiple(bool $multiple): self
    {
        return $this->set('multiple', $multiple);
    }

}