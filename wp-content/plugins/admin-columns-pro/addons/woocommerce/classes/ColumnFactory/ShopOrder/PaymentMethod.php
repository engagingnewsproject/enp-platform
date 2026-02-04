<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class PaymentMethod extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ConditionalFormatTrait;

    public function get_column_type(): string
    {
        return 'column-wc-payment_method';
    }

    public function get_label(): string
    {
        return __('Payment Method', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Order\PaymentMethod());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ShopOrder\PaymentMethod();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta('_payment_method_title');
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\ShopOrder\PaymentMethod();
    }

}