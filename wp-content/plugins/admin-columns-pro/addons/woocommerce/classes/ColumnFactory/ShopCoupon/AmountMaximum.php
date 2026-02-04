<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon;

use AC\FormatterCollection;
use AC\Setting\Config;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACP;
use ACP\Sorting\Type\DataType;

class AmountMaximum extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    private const META_KEY = 'maximum_amount';

    public function get_column_type(): string
    {
        return 'column-wc-maximum_amount';
    }

    public function get_label(): string
    {
        return __('Maximum Amount', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Value\Formatter\ShopCoupon\MaximumAmount())
                     ->add(new Value\Formatter\WcPrice());
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new Editing\ShopCoupon\MaximumAmount();
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new ACP\Search\Comparison\Meta\Number(self::META_KEY);
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta(self::META_KEY, new DataType(DataType::NUMERIC));
    }

}