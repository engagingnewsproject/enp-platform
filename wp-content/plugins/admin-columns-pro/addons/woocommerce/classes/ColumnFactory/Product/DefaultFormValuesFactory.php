<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Product;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value\Formatter;
use ACP;

class DefaultFormValuesFactory extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    public function get_column_type(): string
    {
        return 'column-wc-product_default_form_values';
    }

    public function get_label(): string
    {
        return __('Variation Default', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->add(new Formatter\Product\DefaultFormValues());
    }

}