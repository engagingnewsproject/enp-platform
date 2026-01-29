<?php

declare(strict_types=1);

namespace ACP\Setting\ComponentFactory;

use AC;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class Shortcodes extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Shortcode', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'shortcode',
            $this->get_shortcode_options(),
            $config->get('shortcode', '')
        );
    }

    private function get_shortcode_options(): OptionCollection
    {
        global $shortcode_tags;

        $shortcode_keys = array_keys($shortcode_tags);

        $options = array_combine($shortcode_keys, $shortcode_keys);
        asort($options);

        return OptionCollection::from_array($options);
    }

}