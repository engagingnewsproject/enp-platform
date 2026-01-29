<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;

class File implements Formatter
{

    public function format(Value $value): Value
    {
        $attachment_id = $value->get_value();

        if ( ! is_numeric($attachment_id)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $attachment = get_attached_file($attachment_id);

        if ( ! $attachment) {
            return $value->with_value('<em>' . __('Invalid attachment', 'codepress-admin-columns') . '</em>');
        }

        return $value->with_value(
            ac_helper()->html->link(
                wp_get_attachment_url($attachment_id) ?: '',
                esc_html(basename($attachment)),
                ['target' => '_blank']
            )
        );
    }

}