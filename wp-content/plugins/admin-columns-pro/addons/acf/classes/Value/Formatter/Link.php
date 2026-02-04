<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class Link implements Formatter
{

    public function format(Value $value)
    {
        $link = $value->get_value();

        if (empty($link)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $url = $link['url'] ?? null;

        if ( ! $url) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $label = $link['title'] ?? null;

        if ( ! $label) {
            $label = str_replace(['http://', 'https://'], '', (string)$url);
        }

        if ('_blank' === $link['target']) {
            $label .= '<span class="dashicons dashicons-external" style="font-size: 1em;"></span>';
        }

        return $value->with_value(ac_helper()->html->link((string)$url, $label));
    }

}