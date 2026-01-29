<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use AC\Type\ValueCollection;

class OrderEmails implements Formatter
{

    public function format(Value $value): ValueCollection
    {
        $user_id = $value->get_id();

        $orders = wc_get_orders([
            'customer_id' => $user_id,
            'limit'       => -1,
        ]);

        $email_collection = new ValueCollection($user_id, []);
        $unique_emails = [];

        foreach ($orders as $order) {
            $email = $order->get_billing_email();

            if ($email && ! in_array($email, $unique_emails, true)) {
                $unique_emails[] = $email;
                $email_collection->add(new Value($email));
            }
        }

        if (count($unique_emails) === 0) {
            throw ValueNotFoundException::from_id($user_id);
        }

        return $email_collection;
    }
}
