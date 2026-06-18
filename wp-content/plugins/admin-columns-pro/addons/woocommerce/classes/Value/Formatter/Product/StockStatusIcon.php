<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\Product;

use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class StockStatusIcon implements Formatter
{

    public function format(Value $value)
    {
        switch ($value->get_value()) {
            case 'instock' :
                return $value->with_value(Helper\Icon::create()->yes(__('In stock', 'codepress-admin-columns')));
            case 'outofstock' :
                return $value->with_value(Helper\Icon::create()->no(__('Out of stock', 'codepress-admin-columns')));
            case 'onbackorder' :
                return $value->with_value(
                    Helper\Icon::create()->dashicon(
                        [
                            'icon'    => 'backup',
                            'class'   => 'yellow',
                            'tooltip' => __('On backorder', 'codepress-admin-columns'),
                        ]
                    )
                );
            default:
                return $value;
        }
    }

}