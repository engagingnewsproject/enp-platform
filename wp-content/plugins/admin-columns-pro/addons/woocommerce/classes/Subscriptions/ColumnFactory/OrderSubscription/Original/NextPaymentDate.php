<?php

declare(strict_types=1);

namespace ACA\WC\Subscriptions\ColumnFactory\OrderSubscription\Original;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC;
use ACA\WC\Subscriptions;
use ACP;
use ACP\Column\OriginalColumnFactory;

class NextPaymentDate extends OriginalColumnFactory
{

    protected function get_export(Config $config): ?FormatterCollection
    {
        return FormatterCollection::from_formatter(
            new Subscriptions\Value\Formatter\OrderSubscription\SubscriptionDate('next_payment')
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new WC\Editing\OrderSubscription\Date('next_payment', true);
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new WC\Search\OrderMeta\IsoDate('_schedule_next_payment');
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new WC\Sorting\Order\OrderMeta(
            '_schedule_next_payment',
            new ACP\Sorting\Type\DataType(ACP\Sorting\Type\DataType::DATETIME)
        );
    }

}