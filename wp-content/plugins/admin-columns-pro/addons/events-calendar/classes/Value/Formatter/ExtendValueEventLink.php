<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter;

use AC;
use AC\Type\Value;

class ExtendValueEventLink implements AC\Formatter
{

    private AC\Value\Extended\ExtendedValue $extended_value;

    private string $display;

    public function __construct(AC\Value\Extended\ExtendedValue $extended_value, string $display)
    {
        $this->extended_value = $extended_value;
        $this->display = $display;
    }

    public function format(Value $value): Value
    {
        $link = $this->extended_value->get_link($value->get_id(), $value->get_value())
                                     ->with_params(['display' => $this->display]);

        return $value->with_value(
            $link->render()
        );
    }
}