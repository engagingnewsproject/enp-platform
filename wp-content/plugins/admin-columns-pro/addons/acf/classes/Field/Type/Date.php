<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class Date extends Field
    implements Field\Date, Field\SaveFormat
{

    public function get_display_format(): string
    {
        return (string)$this->settings['display_format'];
    }

    public function get_first_day(): int
    {
        return (int)$this->settings['first_day'];
    }

    public function get_save_format(): string
    {
        return isset($this->settings['save_format'])
            ? $this->parse_jquery_dateformat($this->settings['save_format'])
            : 'Ymd';
    }

    private function parse_jquery_dateformat($format)
    {
        $replace = [
            '^dd^d' => 'j',
            'dd'    => 'd',
            'DD'    => 'l',
            'o'     => 'z',
            'MM'    => 'F',
            '^mm^m' => 'n',
            'mm'    => 'm',
            'yy'    => 'Y',
        ];

        $replace_from = [];
        $replace_to = [];

        foreach ($replace as $from => $to) {
            $replace_from[] = '/' . $from . '/';
            $replace_to[] = $to;
        }

        return preg_replace($replace_from, $replace_to, $format);
    }

}