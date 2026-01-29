<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC;
use AC\Formatter;
use AC\Setting\Control\OptionCollection;

class Date extends AC\Setting\ComponentFactory\DateFormat
{

    private string $date_format;

    public function __construct(string $date_format)
    {
        parent::__construct();

        $this->date_format = $date_format;
    }

    protected function get_default_option(): string
    {
        return 'acf';
    }

    protected function get_date_options(): AC\Setting\Control\OptionCollection
    {
        $options = [
            'diff'       => __('Time Difference', 'codepress-admin-columns'),
            'wp_default' => __('WordPress Date Format', 'codepress-admin-columns'),
            'acf'        => __('ACF', 'codepress-admin-columns'),
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

    protected function get_date_formatter(string $output_format): ?Formatter
    {
        if ('acf' === $output_format) {
            return new Formatter\Date\WordPressDateFormat($this->date_format, 'U');
        }

        return parent::get_date_formatter($output_format);
    }

}