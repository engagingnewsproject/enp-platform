<?php

namespace ACP\Search\Helper\Sql\Comparison;

use ACP\Search\Helper\DateValueFactory;
use ACP\Search\Value;

class EqMonth extends Between
{

    public function bind_value(Value $value): self
    {
        $value_factory = new DateValueFactory($value->get_type());

        return parent::bind_value($value_factory->create_range_month($value->get_value()));
    }

}