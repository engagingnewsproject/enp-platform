<?php

declare(strict_types=1);

namespace ACA\WC;

use AC\Ajax;
use AC\Registerable;

final class Rounding implements Registerable
{

    public function register(): void
    {
        $this->get_ajax_handler()->register();
    }

    protected function get_ajax_handler(): Ajax\Handler
    {
        $handler = new Ajax\Handler();
        $handler->set_action('acp-rounding')
                ->set_callback([$this, 'ajax_send_feedback']);

        return $handler;
    }

    public function ajax_send_feedback(): void
    {
        $price = (float)filter_input(INPUT_GET, 'price');
        $decimals = (int)filter_input(INPUT_GET, 'decimals');

        $rounding = new Helper\Price\Rounding();

        switch (filter_input(INPUT_GET, 'type')) {
            case 'roundup':
                echo $rounding->up($price, $decimals);
                exit;

            case 'rounddown':
                echo $rounding->down($price, $decimals);
                exit;

            default :
                echo $price;
                exit;
        }
    }
}