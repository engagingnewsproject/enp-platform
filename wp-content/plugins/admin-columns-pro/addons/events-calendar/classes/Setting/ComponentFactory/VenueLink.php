<?php

declare(strict_types=1);

namespace ACA\EC\Setting\ComponentFactory;

use AC\Formatter\Post\PostLink;
use AC\FormatterCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;
use AC\Setting\Control\OptionCollection;

class VenueLink extends BaseComponentFactory
{

    private const NAME = 'venue_link_to';

    protected function get_label(Config $config): ?string
    {
        return __('Link to', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array($this->get_display_options()),
            $config->get(self::NAME, '')
        );
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        if ($config->get(self::NAME, '') === 'edit_post') {
            $formatters->add(new PostLink('edit_post'));
        }
    }

    protected function get_display_options(): array
    {
        return [
            ''          => __('None'),
            'edit_post' => __('Edit Post'),
        ];
    }

}