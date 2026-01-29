<?php

declare(strict_types=1);

namespace ACA\WC\Editing\View;

use ACP;

class Notes extends ACP\Editing\View
{

    public function __construct()
    {
        parent::__construct('wc_order_notes');
    }

    public function set_mode(string $mode): ACP\Editing\View
    {
        return $this->set('mode', $mode);
    }

}
