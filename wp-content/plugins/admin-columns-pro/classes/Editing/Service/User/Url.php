<?php

namespace ACP\Editing\Service\User;

use ACP\Editing\Service\BasicStorage;
use ACP\Editing\Storage;
use ACP\Editing\View;

class Url extends BasicStorage
{

    private string $placeholder;

    public function __construct(string $placeholder)
    {
        parent::__construct(new Storage\User\Field('user_url'));

        $this->placeholder = $placeholder;
    }

    public function get_view(string $context): ?View
    {
        return (new View\Url())->set_placeholder($this->placeholder);
    }

}