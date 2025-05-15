<?php

namespace ACA\ACF\Editing\Service;

use ACP\Editing\Service;
use ACP\Editing\Storage;
use ACP\Editing\View;

class Wysiwyg extends Service\Basic
{

    public function __construct(Storage $storage)
    {
        parent::__construct(new View\Wysiwyg(), $storage);
    }

    public function get_value(int $id)
    {
        return wpautop(parent::get_value($id));
    }

}