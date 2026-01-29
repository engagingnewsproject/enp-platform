<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class FileDownload implements AC\Formatter
{

    public function format(Value $value)
    {
        $data = $value->get_value();

        if ( ! is_array($data)) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        $url = ac_helper()->html->tooltip(
            sprintf('<a href="%s" download>%s</a>', $data['url'], $data['name']),
            $data['url']
        );

        return $value->with_value($url);
    }

}