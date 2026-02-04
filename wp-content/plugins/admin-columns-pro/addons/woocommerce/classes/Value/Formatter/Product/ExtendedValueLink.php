<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Value\Extended\ExtendedValue;

class ExtendedValueLink implements Formatter
{

    private ExtendedValue $extended_value;

    private string $class;

    public function __construct(ExtendedValue $extended_value, string $class = '-nopadding -w-large')
    {
        $this->extended_value = $extended_value;
        $this->class = $class;
    }

    public function format(Value $value)
    {
        $label = $value->get_value();

        if ( ! is_scalar($label) || ac_helper()->string->is_empty($label)) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        $link = $this->extended_value->get_link((int)$value->get_id(), (string)$label)
                                     ->with_title(strip_tags(get_the_title($value->get_id())))
                                     ->with_class($this->class);

        $edit_link = get_edit_post_link($value->get_id());

        if ($edit_link) {
            $link->with_edit_link(get_edit_post_link($value->get_id()));
        }

        return $value->with_value(
            $link->render()
        );
    }

}