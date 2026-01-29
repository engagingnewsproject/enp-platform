<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

trait AjaxTrait
{

    public function set_ajax_populate(bool $use_ajax): View
    {
        $this->set('ajax_populate', $use_ajax);

        return $this;
    }

}