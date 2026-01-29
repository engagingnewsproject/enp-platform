<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

class Media extends View
{

    use AttachmentTypeTrait;
    use MultipleTrait;

    public function __construct()
    {
        parent::__construct('media');
    }

}