<?php

namespace ACP\Editing\Service\User;

use ACP\Editing;
use ACP\Editing\Storage;
use ACP\Editing\View;

class Registered extends Editing\Service\BasicStorage
{

    public function __construct()
    {
        parent::__construct(new Storage\User\Field('user_registered'));
    }

    public function get_view(string $context): ?View
    {
        return new Editing\View\DateTime();
    }

}