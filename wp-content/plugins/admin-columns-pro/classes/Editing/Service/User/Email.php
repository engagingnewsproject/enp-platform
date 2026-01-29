<?php

namespace ACP\Editing\Service\User;

use ACP\Editing\Service\BasicStorage;
use ACP\Editing\Storage;
use ACP\Editing\View;

class Email extends BasicStorage
{

    private $placeholder;

    public function __construct(string $placeholder)
    {
        parent::__construct(new Storage\User\Field('user_email'));

        $this->placeholder = $placeholder;
    }

    public function get_view(string $context): View\Email
    {
        return (new View\Email())->set_placeholder($this->placeholder);
    }

}