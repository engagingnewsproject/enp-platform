<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;

class OrderStatuses extends BaseComponentFactory
{

    private array $default_statuses;

    public function __construct(array $default_statuses = [])
    {
        $this->default_statuses = $default_statuses;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Order Status', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            'order_status',
            OptionCollection::from_array(wc_get_order_statuses()),
            $config->get('order_status', $this->default_statuses),
            null,
            true
        );
    }

}