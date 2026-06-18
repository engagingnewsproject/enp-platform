<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

trait MinMaxTrait
{

    public function set_min(?float $min = null): View
    {
        if ($min) {
            $this->set('range_min', $min);
        }

        return $this;
    }

    public function set_max(?float $max = null): View
    {
        if ($max) {
            $this->set('range_max', $max);
        }

        return $this;
    }

}