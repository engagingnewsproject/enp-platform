<?php

declare(strict_types=1);

namespace ACA\WC;

use AC;
use AC\Registerable;
use ACP\Settings\ListScreen\TableElement\FilterPostDate;
use ACP\Settings\ListScreen\TableElements;

class Admin implements Registerable
{

    public function register(): void
    {
        add_action('ac/admin/settings/table_elements', [$this, 'add_table_elements'], 10, 2);
    }

    public function add_table_elements(TableElements $collection, AC\TableScreen $table_screen): void
    {
        switch ((string)$table_screen->get_id()) {
            case 'shop_order' :
                $collection->add(new Setting\TableElement\FilterOrderCustomer(), 34);
                break;
            case 'wc_order' :
                $collection->add(new Setting\TableElement\FilterOrderDate(), 34);
                $collection->add(new Setting\TableElement\FilterOrderSubType(), 34);
                $collection->add(new Setting\TableElement\FilterOrderCustomer(), 34);

                break;
            case 'product' :
                $collection->add(new Setting\TableElement\FilterProductCategory(), 32)
                           ->add(new Setting\TableElement\FilterProductStockStatus(), 32)
                           ->add(new Setting\TableElement\FilterProductType(), 32);

                $collection->remove(new FilterPostDate());

                break;
            case 'shop_subscription' :
            case 'wc_order_subscription' :
                $collection->add(new Setting\TableElement\FilterSubscriptionProduct(), 34)
                           ->add(new Setting\TableElement\FilterSubscriptionPayment(), 34)
                           ->add(new Setting\TableElement\FilterSubscriptionCustomer(), 34);

                break;
        }
    }

}