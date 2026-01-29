<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopCoupon;

use AC\Formatter\Post\Meta;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\CouponLimit;
use ACA\WC\Sorting;
use ACA\WC\Value;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\Sorting\Type\DataType;

class Limit extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\IntegerFormattableTrait;
    use WooCommerceGroupTrait;

    private CouponLimit $coupon_limit;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        CouponLimit $coupon_limit
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->coupon_limit = $coupon_limit;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->coupon_limit->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-shop-coupon_limit';
    }

    public function get_label(): string
    {
        return __('Coupon limit', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)->prepend(new Meta($config->get('coupon_limit', '')));
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        $view = new ACP\Editing\View\Number();
        $view->set_min(0);
        $view->set_step('1');

        return new ACP\Editing\Service\Basic(
            $view,
            new ACP\Editing\Storage\Post\Meta($config->get('coupon_limit', ''))
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\Post\Meta($config->get('coupon_limit', ''), new DataType(DataType::NUMERIC));
    }

}