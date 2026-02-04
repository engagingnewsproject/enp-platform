<?php

declare(strict_types=1);

namespace ACA\EC\Setting\ComponentFactory;

use AC\Formatter\Id;
use AC\Formatter\Post\Meta;
use AC\Formatter\Post\PostTitle;
use AC\FormatterCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;
use AC\Setting\Control\OptionCollection;

class OrganizerDisplay extends BaseComponentFactory
{

    private const PROPERTY_EMAIL = 'email';
    private const PROPERTY_ID = 'id';
    private const PROPERTY_TITLE = 'title';
    private const PROPERTY_PHONE = 'phone';
    private const PROPERTY_WEBSITE = 'website';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_select(
            'organizer_display',
            OptionCollection::from_array(
                $this->get_display_options()
            ),
            $config->get('organizer_display', self::PROPERTY_TITLE)
        );
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        switch ($config->get('organizer_display', self::PROPERTY_TITLE)) {
            case self::PROPERTY_EMAIL :
                $formatters->add(new Meta('_OrganizerEmail'));
                break;
            case self::PROPERTY_WEBSITE :
                $formatters->add(new Meta('_OrganizerWebsite'));
                break;
            case self::PROPERTY_PHONE :
                $formatters->add(new Meta('_OrganizerPhone'));
                break;
            case self::PROPERTY_ID :
                $formatters->add(new Id());
                break;
            case self::PROPERTY_TITLE :
            default :
                $formatters->add(new PostTitle());
                break;
        }
    }

    protected function get_display_options(): array
    {
        $options = [
            self::PROPERTY_TITLE   => __('Title'),
            self::PROPERTY_ID      => __('ID'),
            self::PROPERTY_PHONE   => __('Phone', 'codepress-admin-columns'),
            self::PROPERTY_EMAIL   => __('Email', 'codepress-admin-columns'),
            self::PROPERTY_WEBSITE => __('Website', 'codepress-admin-columns'),
        ];

        asort($options);

        return $options;
    }

}