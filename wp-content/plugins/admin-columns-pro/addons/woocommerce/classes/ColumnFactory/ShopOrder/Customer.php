<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\Order\CustomerProperty;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use WP_Roles;

class Customer extends ACP\Column\AdvancedColumnFactory
{

    use WooCommerceGroupTrait;
    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;

    private CustomerProperty $customer_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        CustomerProperty $customer_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->customer_property = $customer_property;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->customer_property->create($config));
    }

    public function get_column_type(): string
    {
        return 'column-wc-order_customer';
    }

    public function get_label(): string
    {
        return __('Customer', 'codepress-admin-columns');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->prepend(new Formatter\Order\CustomerId());
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\Post\RelatedMeta\UserFactory())->create(
            $config->get('display_author_as', ''),
            '_customer_user'
        );
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        switch ($config->get('display_author_as', '')) {
            case 'roles':
                return new Search\ShopOrder\Customer\Meta\Serialized\Role($this->get_roles());

            case 'custom_field':
                return new Search\ShopOrder\Customer\Meta($config->get('field', ''));
        }

        return new Search\ShopOrder\Customer();
    }

    private function get_roles(): array
    {
        $options = [];
        $roles = new WP_Roles();

        foreach ($roles->roles as $key => $role) {
            $options[$key] = translate_user_role($role['name']);
        }

        return $options;
    }

}