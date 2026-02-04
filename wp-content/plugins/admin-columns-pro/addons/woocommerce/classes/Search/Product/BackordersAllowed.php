<?php

declare(strict_types=1);

namespace ACA\WC\Search\Product;

use AC\Helper\Select\Options;
use ACP\Search\Comparison;
use ACP\Search\Operators;

class BackordersAllowed extends Comparison\Meta
    implements Comparison\Values
{

    public function __construct()
    {
        $operators = new Operators([
            Operators::EQ,
        ]);

        parent::__construct($operators, '_backorders');
    }

    public function get_values(): Options
    {
        return Options::create_from_array([
            'no'     => __('Do not allow', 'woocommerce'),
            'notify' => __('Allow, but notify customer', 'woocommerce'),
            'yes'    => __('Allow', 'woocommerce'),
        ]);
    }

}