<?php

declare(strict_types=1);

namespace ACA\EC\Value\Formatter;

use AC;
use AC\Type\Value;
use LogicException;

class CallbackWithId implements AC\Formatter
{

    private string $callback;

    public function __construct(string $callback)
    {
        $this->callback = $callback;
    }

    public function format(Value $value): Value
    {
        if ( ! function_exists($this->callback)) {
            throw new LogicException('Function "' . $this->callback . '" does not exists');
        }

        return $value->with_value(call_user_func($this->callback, $value->get_id()));
    }

}