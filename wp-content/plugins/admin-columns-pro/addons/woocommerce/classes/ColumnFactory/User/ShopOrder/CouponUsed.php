<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Value\Formatter\User\ShopOrder\CouponsUsed;
use ACP\Column\AdvancedColumnFactory;
use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

class CouponUsed extends AdvancedColumnFactory
{

    use FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    public function get_label(): string
    {
        return __('Coupons Used', 'codepress-admin-columns');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user_coupons_used';
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new CouponsUsed());
    }

}