<?php

declare(strict_types=1);

namespace ACA\WC\Setting\TableElement;

use ACP;

class FilterSubscriptionPayment extends ACP\Settings\ListScreen\TableElement
{

    public function __construct()
    {
        parent::__construct(
            'hide_filter_subscription_payment',
            __('Payment Method', 'codepress-admin-columns'),
            'element',
            ACP\Settings\ListScreen\TableElement\Filters::NAME
        );
    }

}