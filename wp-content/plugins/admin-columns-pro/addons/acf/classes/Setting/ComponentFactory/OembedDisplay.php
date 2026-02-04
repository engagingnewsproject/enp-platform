<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC;
use AC\FormatterCollection;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use ACA\ACF\Value\Formatter\OembedVideo;

class OembedDisplay extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Display format', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'oembed',
            OptionCollection::from_array([
                ''      => __('Url'),
                'video' => __('Video'),
            ]),
            $config->get('oembed', '')
        );
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        if ($config->get('oembed') === 'video') {
            $formatters->add(new OembedVideo());
        } else {
            $formatters->add(new AC\Formatter\Linkable());
        }
    }

}