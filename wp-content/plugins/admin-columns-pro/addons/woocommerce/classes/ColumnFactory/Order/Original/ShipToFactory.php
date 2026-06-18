<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Original;

use AC\Formatter\PregReplace;
use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACP;
use ACP\Column\OriginalColumnFactory;

class ShipToFactory extends OriginalColumnFactory
{

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\Order\Address\FullAddress(
            new WC\Type\AddressType(WC\Type\AddressType::SHIPPING)
        );
    }

    protected function get_export(Config $config): ?FormatterCollection
    {
        return new FormatterCollection([
            new WC\Value\Formatter\Order\OrderAddress(WC\Type\AddressType::shipping(), ''),
            (new PregReplace())->replace_br(),
        ]);
    }

}