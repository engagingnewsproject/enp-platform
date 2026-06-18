<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User\Profile;

use AC\Formatter;
use AC\Type\Value;

class DisplayProfileFieldData implements Formatter
{

    private int $field_id;

    public function __construct(int $field_id)
    {
        $this->field_id = $field_id;
    }

    public function format(Value $value)
    {
        return $value->with_value(
            bp_get_profile_field_data([
                'field'   => $this->field_id,
                'user_id' => $value->get_id(),
            ])
        );
    }

}