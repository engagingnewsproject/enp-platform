<?php

namespace ACA\WC\Column\Order\Original;

use AC;
use ACA\WC;
use ACP;

class Billing extends AC\Column implements ACP\Search\Searchable, ACP\Export\Exportable
{

    public function __construct()
    {
        $this->set_type('billing_address')
             ->set_original(true);
    }

    public function search()
    {
        return new WC\Search\Order\Address\FullAddress(
            new WC\Type\AddressType(WC\Type\AddressType::BILLING)
        );
    }

    public function export()
    {
        return new WC\Export\Order\Billing();
    }

}