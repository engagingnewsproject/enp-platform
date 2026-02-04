<?php

declare(strict_types=1);

namespace ACA\WC\Value\Formatter\User;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\Value;
use ACA\WC\Value\ExtendedValue;

class OrderCount implements Formatter
{

    private array $order_status;

    public function __construct(array $order_status = [])
    {
        $this->order_status = $order_status;
    }

    public function format(Value $value)
    {
        $user_id = (int)$value->get_id();

        $user = get_userdata($user_id);

        if ( ! $user) {
            throw ValueNotFoundException::from_id($user_id);
        }

        $args = [
            'customer_id' => $user->ID,
            'limit'       => -1,
            'return'      => 'ids',
        ];

        if ( ! empty($this->order_status)) {
            $args['status'] = $this->order_status;
        }

        $orders = wc_get_orders($args);

        if (empty($orders)) {
            throw ValueNotFoundException::from_id($user->ID);
        }

        $count = count($orders);

        $user_label = ac_helper()->user->get_formatted_name($user);

        $link = (new ExtendedValue\User\Orders())
            ->get_link($user_id, (string)$count)
            ->with_title(
                sprintf(
                    __('Recent orders by %s', 'codepress-admin-columns'),
                    sprintf('”%s”', $user_label),
                )
            );

        if ($this->order_status) {
            $link = $link->with_params([
                'order_status' => $this->order_status,
            ]);
        }

        return $value->with_value(
            $link->render()
        );
    }

}