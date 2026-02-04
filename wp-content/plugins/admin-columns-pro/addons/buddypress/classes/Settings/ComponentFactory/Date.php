<?php

declare(strict_types=1);

namespace ACA\BP\Settings\ComponentFactory;

use AC;
use AC\Formatter;
use AC\Setting\Control\OptionCollection;
use ACA\BP\Value\Formatter\User\LastActivityDateFormat;

class Date extends AC\Setting\ComponentFactory\DateFormat
{

    protected function get_default_option(): string
    {
        return 'wp_default';
    }

    protected function get_date_options(): AC\Setting\Control\OptionCollection
    {
        $options = [
            'diff'       => __('Time Difference', 'codepress-admin-columns'),
            'wp_default' => __('WordPress Date Format', 'codepress-admin-columns'),
            'bp_diff'    => __('BuddyPress', 'codepress-admin-columns'),
        ];

        $formats = [
            'j F Y',
            'Y-m-d',
            'm/d/Y',
            'd/m/Y',
        ];

        foreach ($formats as $format) {
            $options[$format] = wp_date($format);
        }

        return OptionCollection::from_array($options);
    }

    protected function get_date_formatter(string $format): ?Formatter
    {
        return $format === 'bp_diff'
            ? new LastActivityDateFormat()
            : parent::get_date_formatter($format);
    }

}