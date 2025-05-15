<?php

namespace ACA\WC\Search\Order;

use AC\Helper\Select\Options;
use ACA\WC\Search;
use ACP;
use ACP\Search\Operators;

class Status extends OrderField implements ACP\Search\Comparison\Values
{

    private $options;

    public function __construct(array $options = null)
    {
        parent::__construct(
            'status',
            new Operators([
                Operators::EQ,
                Operators::NEQ,
            ])
        );

        if ($options === null) {
            $options = wc_get_order_statuses();
        }

        $this->options = $options;
    }

    public function get_values(): Options
    {
        return Options::create_from_array($this->options);
    }

}