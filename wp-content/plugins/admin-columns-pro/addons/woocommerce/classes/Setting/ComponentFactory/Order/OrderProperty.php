<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\FormatterCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use ACA\WC\Value\Formatter;

class OrderProperty extends BaseComponentFactory
{

    public const NAME = 'order_display';
    public const TYPE_ID = 'id';
    public const TYPE_DATE = 'date';
    public const TYPE_AMOUNT = 'order';
    public const TYPE_STATUS = 'status';
    public const TYPE_SUMMARY = 'summary';

    protected function get_label(Config $config): ?string
    {
        return __('Order Display', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            OptionCollection::from_array(
                [
                    self::TYPE_ID      => __('ID', 'codepress-admin-columns'),
                    self::TYPE_DATE    => __('Date', 'codepress-admin-columns'),
                    self::TYPE_AMOUNT  => __('Amount', 'codepress-admin-columns'),
                    self::TYPE_STATUS  => __('Status', 'codepress-admin-columns'),
                    self::TYPE_SUMMARY => __('Summary', 'codepress-admin-columns'),
                ]
            ),
            $config->get(self::NAME, self::TYPE_DATE)
        );
    }

    protected function add_formatters(Config $config, FormatterCollection $formatters): void
    {
        switch ($config->get(self::NAME, '')) {
            case self::TYPE_ID:
                $formatters->add(new Formatter\Order\EditOrderUrl());
                break;
            case self::TYPE_AMOUNT:
                $formatters->add(new Formatter\Order\OrderTotal());
                break;
            case self::TYPE_STATUS:
                $formatters->add(new Formatter\Order\StatusLabel());
                break;
            case self::TYPE_DATE:
                $formatters->add(new Formatter\Order\DateCreated());
                $formatters->add(new Formatter\Order\EditOrderUrl());
                break;
            case self::TYPE_SUMMARY:
                $formatters->add(new Formatter\Order\Summary());
                break;
        }
    }

}