<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Type\Value;

class OptionLabel implements AC\Formatter
{

    private AC\Setting\Control\OptionCollection $option_collection;

    private $default;

    public function __construct(AC\Setting\Control\OptionCollection $option_collection, $default = false)
    {
        $this->option_collection = $option_collection;
        $this->default = $default;
    }

    public function format(Value $value): Value
    {
        foreach ($this->option_collection as $option) {
            if ($option->get_value() === $value->get_value()) {
                return $value->with_value($option->get_label());
            }
        }

        return $value->with_value($this->default);
    }

}