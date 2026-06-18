<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\Activity;

use AC\Formatter;
use AC\Type\Value;
use BP_Activity_Activity;
use LogicException;

class ActivityProperty implements Formatter
{

    private string $property;

    public function __construct(string $property)
    {
        $this->property = $property;
    }

    public function format(Value $value): Value
    {
        $activity = new BP_Activity_Activity($value->get_id());

        if ( ! property_exists($activity, $this->property)) {
            throw new LogicException('Property does not exist: ' . $this->property);
        }

        return $value->with_value($activity->{$this->property});
    }

}