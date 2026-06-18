<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC;
use AC\Formatter;
use AC\Helper;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\Custom;
use AC\Setting\Control\OptionCollection;

class Time extends AC\Setting\ComponentFactory\DateFormat
{

    private string $time_format;

    public function __construct(string $time_format)
    {
        parent::__construct();

        $this->time_format = $time_format;
    }

    protected function get_label(AC\Setting\Config $config): ?string
    {
        return __('Display Time Format', 'codepress-admin-columns');
    }

    protected function get_default_option(): string
    {
        return 'acf';
    }

    protected function get_date_options(): OptionCollection
    {
        $options = [
            'wp_default' => __('WordPress Date Format', 'codepress-admin-columns'),
            'acf'        => __('ACF', 'codepress-admin-columns'),
        ];

        $formats = [
            'g:i a',
            'g:i A',
            'H:i',
            'H:i:s',
        ];

        foreach ($formats as $format) {
            $options[$format] = wp_date($format);
        }

        return OptionCollection::from_array($options);
    }

    protected function get_input(Config $config): ?Input
    {
        $format_codes = [];

        foreach (['g:i a', 'g:i A', 'H:i', 'H:i:s'] as $format) {
            $format_codes[$format] = $format;
        }

        return new Custom(
            'date_format',
            null,
            [
                'wp_date_format' => Helper\Date::create()->get_time_format(),
                'wp_date_info'   => sprintf(
                    __('The %s can be changed in %s.', 'codepress-admin-columns'),
                    __('WordPress Date Format', 'codepress-admin-columns'),
                    Helper\Html::create()->link(
                        admin_url('options-general.php') . '#time_format_custom_radio',
                        strtolower(__('General Settings'))
                    )
                ),
                'format_codes'   => $format_codes,
            ]
        );
    }

    protected function get_date_formatter(string $output_format): Formatter
    {
        if ('acf' === $output_format) {
            return new Formatter\Date\DateFormat($this->time_format, 'U');
        }

        return parent::get_date_formatter($output_format);
    }

}
