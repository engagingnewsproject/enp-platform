<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class Maps implements AC\Formatter
{

    public function format(Value $value)
    {
        $maps = $value->get_value();

        if ( ! is_array($maps)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        if (empty($maps['latitude']) || empty($maps['longitude'])) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $parts = [
            sprintf('%s: %s', __('Latitude', 'codepress-admin-columns'), $maps['latitude']),
            sprintf('%s: %s', __('Longitude', 'codepress-admin-columns'), $maps['longitude']),
            sprintf('%s: %s', __('Zoom', 'codepress-admin-columns'), $maps['zoom']),
        ];

        $formatted = ac_helper()->html->link(
            $this->get_link($maps),
            ac_helper()->html->tooltip(__('View'), implode('<br>', $parts)),
            ['target' => '_blank']
        );

        return $value->with_value($formatted);
    }

    protected function get_link($value)
    {
        return sprintf(
            'https://www.google.com/maps/search/?api=1&query=%s,%s&z=%s',
            $value['latitude'],
            $value['longitude'],
            $value['zoom']
        );
    }

}