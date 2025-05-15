<?php

namespace ACA\WC\Column\Order;

use AC;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACP;

class OrderId extends AC\Column implements ACP\Search\Searchable, ACP\ConditionalFormat\Formattable,
                                           ACP\Sorting\Sortable
{

    use ACP\ConditionalFormat\ConditionalFormatTrait;

    public function __construct()
    {
        $this->set_type('column-order_id')
             ->set_label(__('ID'))
             ->set_group('woocommerce');
    }

    public function get_raw_value($id)
    {
        return $id;
    }

    public function search()
    {
        return new Search\Order\OrderId();
    }

    public function sorting()
    {
        return new Sorting\Order\OrderBy('id');
    }

}