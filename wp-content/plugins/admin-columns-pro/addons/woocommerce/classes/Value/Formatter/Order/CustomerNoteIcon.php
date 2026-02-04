<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Order;

use AC\Formatter;
use AC\Type\Value;

class CustomerNoteIcon implements Formatter
{

    public function format(Value $value)
    {
        $icon = ac_helper()->icon->dashicon(['icon' => 'media-text', 'class' => 'gray']);

        return $value->with_value(ac_helper()->html->tooltip($icon, $value->get_value()));
    }

}