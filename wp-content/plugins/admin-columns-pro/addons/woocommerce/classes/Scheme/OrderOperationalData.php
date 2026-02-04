<?php

declare(strict_types=1);

namespace ACA\WC\Scheme;

interface OrderOperationalData
{

    public const TABLE = 'wc_order_operational_data';

    public const DATE_COMPLETED_GMT = 'date_completed_gmt';
    public const DATE_PAID_GMT = 'date_paid_gmt';
    public const WOOCOMMERCE_VERSION = 'woocommerce_version';
    public const CREATED_VIA = 'created_via';
    public const DISCOUNT_TOTAL_AMOUNT = 'discount_total_amount';
    public const DISCOUNT_TAX_AMOUNT = 'discount_tax_amount';

}