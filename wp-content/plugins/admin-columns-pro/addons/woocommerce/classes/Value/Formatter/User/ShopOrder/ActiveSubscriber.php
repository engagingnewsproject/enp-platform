<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User\ShopOrder;

use AC\Formatter;
use AC\Type\Value;

class ActiveSubscriber implements Formatter
{

    public function format(Value $value)
    {
        $active = wcs_user_has_subscription((int)$value->get_id(), '', 'active');

        return $value->with_value($active ? '1' : '0');
    }

}