<?php

declare(strict_types=1);

namespace ACA\WC\Type;

use AC\Type\Uri;

class OrderSubscriptionTableUrl extends Uri
{

    public function __construct()
    {
        parent::__construct((string)admin_url('admin.php'));

        $this->add('page', 'wc-orders--shop_subscription');
    }
}