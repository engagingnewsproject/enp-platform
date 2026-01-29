<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory\User;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class GravatarImageSize extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Gravatar Image Size', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return new Input\Open(
            'gravatar_size',
            'number',
            (string)$config->get('gravatar_size', 96),
            null,
            null,
            'px'
        );
    }

}