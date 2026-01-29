<?php

namespace ACP\Editing\View;

use ACP\Editing\View;

class TextArea extends View implements Placeholder, MaxLength
{

    use MaxlengthTrait;
    use PlaceholderTrait;

    public function __construct()
    {
        parent::__construct('textarea');

        $this->set_rows(6);
    }

    public function set_rows(int $rows): TextArea
    {
        $this->set('rows', (string)$rows);

        return $this;
    }

}