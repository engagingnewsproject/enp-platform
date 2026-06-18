<?php

namespace ACP\Search\Helper\Sql\Comparison;

use ACP\Search\Helper\DateValueFactory;
use ACP\Search\Value;
use DateTime;

class WithinDays extends Between
{

    public function bind_value(Value $value): self
    {
        $end = new DateTime();
        $end->modify(sprintf('+%s days', $value->get_value()))
            ->setTime(23, 59, 59);

        $value_factory = new DateValueFactory($value->get_type());

        return parent::bind_value($value_factory->create_range(new DateTime(), $end));
    }

}