<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

trait WeekstartTrait
{

    public function set_week_start(int $week_start): View
    {
        return $this->set('weekstart', $week_start);
    }

}