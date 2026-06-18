<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Original;

use AC\Formatter\PregReplace;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\Type\AddressType;
use ACA\WC\Value\Formatter\Order\OrderAddress;
use ACP\Column\OriginalColumnFactory;

class ShippingAddress extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new OrderAddress(AddressType::shipping(), ''),
            (new PregReplace())->replace_br(),
        ]);
    }
}