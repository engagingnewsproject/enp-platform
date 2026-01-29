<?php

declare(strict_types=1);

namespace ACA\MetaBox\Value\Formatter;

use AC;
use AC\Type\Value;

class FileNames implements AC\Formatter
{

    public function format(Value $value)
    {
        $files = $value->get_value();

        if (is_numeric($files)) {
            return $value->with_value(wp_get_attachment_url($files));
        }

        $results = [];

        foreach ($files as $id => $file) {
            if (is_numeric($file)) {
                $results[] = wp_get_attachment_url($file);
                continue;
            }

            $results[] = $file['url'];
        }

        return $value->with_value(implode(', ', $results));
    }

}