<?php

namespace ACA\WC\Editing\Order;

use ACP;
use ACP\Editing\View;
use WC_Payment_Gateway;

class PaymentMethod implements ACP\Editing\Service
{

    public function get_view(string $context): ?View
    {
        return new ACP\Editing\View\Select($this->get_payment_methods());
    }

    private function get_payment_methods()
    {
        $payment_gateways = WC()->payment_gateways()->payment_gateways();
        $options = [];
        /**
         * @var WC_Payment_Gateway $gateway
         */
        foreach ($payment_gateways as $key => $gateway) {
            $options[$key] = $gateway->get_title();
        }

        return $options;
    }

    public function get_value(int $id)
    {
        return wc_get_order($id)->get_payment_method();
    }

    public function update(int $id, $data): void
    {
        $methods = $this->get_payment_methods();
        $order = wc_get_order($id);
        $order->set_payment_method($data);
        $order->set_payment_method_title($methods[$data] ?? $data);

        $order->save();
    }

}
