<?php

declare(strict_types=1);

namespace ACP\Formatter\Plugin;

use AC\CollectionFormatter;
use AC\Exception\ValueNotFoundException;
use AC\Type\Value;
use AC\Type\ValueCollection;
use ACP\Value\ExtendedValue\NetworkSites\Plugins;

class CountWithTooltip implements CollectionFormatter
{

    private Plugins $extended_value;

    public function __construct(Plugins $extended_value)
    {
        $this->extended_value = $extended_value;
    }

    public function format(ValueCollection $collection): Value
    {
        if ($collection->count() === 0) {
            throw ValueNotFoundException::from_id($collection->get_id());
        }

        $link = $this->extended_value->get_link(
            $collection->get_id(),
            (string)$collection->count()
        );

        return new Value(
            $collection->get_id(),
            $link->render()
        );
    }
}
