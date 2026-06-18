<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ProductVariation;

use AC\Setting\Config;
use ACA\WC\ColumnFactory;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;

class WeightFactory extends ColumnFactory\Product\WeightFactory
{

    public function get_column_type(): string
    {
        return 'column-wc-variation_weight';
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ProductVariation\Weight();
    }

}