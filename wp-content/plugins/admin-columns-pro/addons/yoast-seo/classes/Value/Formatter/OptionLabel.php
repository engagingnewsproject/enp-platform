<?php

declare(strict_types=1);

namespace ACA\YoastSeo\Value\Formatter;

use AC;
use AC\Setting\Control\OptionCollection;
use AC\Type\Value;

class OptionLabel implements AC\Formatter
{

    private OptionCollection $option_collection;

    private ?string $default;

    public function __construct(OptionCollection $option_collection, ?string $default = null)
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