<?php

declare(strict_types=1);

namespace ACA\EC\Setting\ComponentFactory;

use AC\Formatter\Post\PostTitle;
use AC\FormatterCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use ACA\EC\Value\Formatter\CallbackWithId;

class VenueDisplay extends BaseComponentFactory
{

    public const PROPERTY_ADDRESS = 'address';
    public const PROPERTY_CITY = 'city';
    public const PROPERTY_COUNTRY = 'country';
    public const PROPERTY_PHONE = 'phone';
    public const PROPERTY_WEBSITE = 'website';
    public const PROPERTY_TITLE = 'title';
    public const PROPERTY_ZIP = 'zip';

    protected function get_label(Config $config): ?string
    {
        return __('Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'post',
            $this->get_display_options(),
            $config->get('post', self::PROPERTY_TITLE)
        );
    }

    protected function get_display_options(): OptionCollection
    {
        $options = [
            self::PROPERTY_ADDRESS => __('Address', 'codepress-admin-columns'),
            self::PROPERTY_CITY    => __('City', 'codepress-admin-columns'),
            self::PROPERTY_COUNTRY => __('Country', 'codepress-admin-columns'),
            self::PROPERTY_PHONE   => __('Phone', 'codepress-admin-columns'),
            self::PROPERTY_TITLE   => __('Name', 'codepress-admin-columns'),
            self::PROPERTY_WEBSITE => __('Website', 'codepress-admin-columns'),
            self::PROPERTY_ZIP     => __('ZIP', 'codepress-admin-columns'),
        ];

        asort($options);

        return OptionCollection::from_array($options);
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        switch ($config->get('post', self::PROPERTY_TITLE)) {
            case self::PROPERTY_ADDRESS:
                $formatters->add(new CallbackWithId('tribe_get_address'));
                break;
            case self::PROPERTY_CITY:
                $formatters->add(new CallbackWithId('tribe_get_city'));
                break;
            case self::PROPERTY_COUNTRY:
                $formatters->add(new CallbackWithId('tribe_get_country'));
                break;
            case self::PROPERTY_PHONE:
                $formatters->add(new CallbackWithId('tribe_get_phone'));
                break;
            case self::PROPERTY_TITLE:
                $formatters->add(new PostTitle());
                break;
            case self::PROPERTY_WEBSITE:
                $formatters->add(new CallbackWithId('tribe_get_venue_website_url'));
                break;
            case self::PROPERTY_ZIP:
                $formatters->add(new CallbackWithId('tribe_get_zip'));
                break;
        }
    }

}