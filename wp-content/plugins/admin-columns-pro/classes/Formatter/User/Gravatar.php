<?php

declare(strict_types=1);

namespace ACP\Formatter\User;

use AC;
use AC\Type\Value;

class Gravatar implements AC\Formatter
{

    private ?int $size;

    public function __construct(?int $size = null)
    {
        $this->size = $size;
    }

    public function format(Value $value)
    {
        $gravatar = get_avatar($value->get_id(), $this->size);

        if ( ! $gravatar) {
            throw AC\Exception\ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($gravatar);
    }

}