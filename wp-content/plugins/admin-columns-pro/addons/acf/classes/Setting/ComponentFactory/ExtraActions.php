<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use ACA\ACF\Helper;

class ExtraActions extends BaseComponentFactory
{

    private Helper $helper;

    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Extra Actions', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        if ( ! $config->has('type')) {
            return null;
        }

        $url = $this->helper->get_field_edit_link($config->get('type'));

        if ( ! $url) {
            return null;
        }

        $button = sprintf(
            '<a target="_blank" class="acui-button acui-button-default acu-mr-2" href="%s">%s</a>',
            $url,
            __('Edit Field', 'codepress-admin-columns')
        );

        return new Input\Custom(
            'message',
            null,
            [
                'message' => $button,
            ]
        );
    }

}