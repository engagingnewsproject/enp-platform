<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\Value\Formatter\OrderSubscription;

use AC\Type\Value;
use LogicException;
use WC_Subscription;

class SubscriptionDate extends SubscriptionMethod
{

    private string $date_type;

    public function __construct(string $date_type)
    {
        $this->date_type = $date_type;
        $this->validate();
    }

    private function validate(): void
    {
        if ( ! in_array(
            $this->date_type,
            ['start', 'date_created', 'trial_end', 'next_payment', 'last_order_date_created', 'end']
        )) {
            throw new LogicException(sprintf('Date type "%s" not supported', $this->date_type));
        }
    }

    protected function get_subscription_value(WC_Subscription $subscription, Value $value): Value
    {
        return $value->with_value($subscription->get_date($this->date_type) ?: '');
    }

}