<?php

namespace ACP\Editing\Service;

use ACP\Editing\Storage;
use ACP\Editing\View;

final class Basic extends BasicStorage
{

    private View $view;

    public function __construct(View $view, Storage $storage)
    {
        parent::__construct($storage);

        $this->view = $view;
    }

    public function get_view(string $context): ?View
    {
        return $this->view;
    }

}