<?php

namespace ACP\Column\NetworkUser;

use AC;
use ACP\Export;
use ACP\Export\Exportable;

class Blogs extends AC\Column implements Exportable
{

    public function __construct()
    {
        $this->set_original(true);
        $this->set_type('blogs');
    }

    public function export()
    {
        return new Export\Model\NetworkUser\Blogs();
    }

}