<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Maps implements Formatter
{

    public function format(Value $value)
    {
        $maps_data = $value->get_value();

        if ( ! $maps_data || ! is_array($maps_data)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $url = $this->get_maps_url($maps_data);
        $label = $maps_data['address'] ?: 'Google Maps';

        return $value->with_value( sprintf('<a href="%s" target="_blank">%s</a>', $url, $label) );
    }

    private function get_maps_url($data): string
    {
        $base = 'https://www.google.com/maps/search/?api=1';

        $take_arguments = ['address', 'lat', 'lng'];
        $arguments = [];
        foreach ($take_arguments as $arg) {
            if (isset($data[$arg])) {
                $arguments[] = $data[$arg];
            }
        }

        return add_query_arg(
            [
                'query' => implode(',', $arguments),
                'zoom'  => $data['zoom'] ?? 15,
            ],
            $base
        );
    }

}