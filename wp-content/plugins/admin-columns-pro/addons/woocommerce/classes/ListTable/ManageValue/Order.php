<?php

declare(strict_types=1);

namespace ACA\WC\ListTable\ManageValue;

use AC\Table\ManageValue\RenderFactory;
use AC\TableScreen\ManageValueService;
use AC\Type\ColumnId;
use AC\Type\Value;
use DomainException;

class Order implements ManageValueService
{

    private string $order_type;

    private RenderFactory $factory;

    public function __construct(string $order_type, RenderFactory $factory)
    {
        $this->order_type = $order_type;
        $this->factory = $factory;
    }

    public function register(): void
    {
        $action = sprintf('woocommerce_%s_list_table_custom_column', $this->order_type);

        if (did_action($action)) {
            throw new DomainException(sprintf("Method should be called before the %s action.", $action));
        }

        add_action($action, [$this, 'render_value'], 100, 2);
    }

    public function render_value(...$args): void
    {
        [$column_name, $order] = $args;

        // TODO pass WC_Order object to Value object

        $formatter = $this->factory->create(new ColumnId((string)$column_name));

        if ($formatter) {
            echo $formatter->format(new Value($order->get_id()));
        }
    }

}