<?php

namespace ACP\Editing\Service\User;

use ACP\Editing\Service\BasicStorage;
use ACP\Editing\Storage;
use ACP\Editing\View;
use ACP\Editing\View\Text;

class Nicename extends BasicStorage
{

    public function __construct()
    {
        parent::__construct(new Storage\User\Field('user_nicename'));
    }

    public function get_view(string $context): View
    {
        return (new Text())->set_placeholder(__('Author Slug', 'codepress-admin-columns'));
    }

}