<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\Formatter\User\Meta;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\User\AddressType;
use ACA\WC\Type;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;

class CountryFactory extends ACP\Column\AdvancedColumnFactory
{

    use ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private AddressType $address_type;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        AddressType $address_type
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->address_type = $address_type;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->address_type->create($config));
    }

    public function get_label(): string
    {
        return __('Country', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-country';
    }

    private function get_address_type(Config $config): Type\AddressType
    {
        return new Type\AddressType($config->get(AddressType::NAME, 'billing'));
    }

    private function get_address_meta_key(Config $config): string
    {
        return sprintf('%s_%s', $this->get_address_type($config), 'country');
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        return parent::get_formatters($config)
                     ->add(new Meta($this->get_address_meta_key($config)))
                     ->add(new Formatter\WcFormattedCountry());
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return new Search\User\Country(
            $this->get_address_meta_key($config),
            $this->get_countries()
        );
    }

    private function get_countries(): array
    {
        static $countries;

        if (null === $countries) {
            $wc_countries = WC()->countries;

            $countries = $wc_countries
                ? (array)$wc_countries->get_countries()
                : [];
        }

        return $countries;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return new ACP\Editing\Service\Basic(
            new ACP\Editing\View\Select($this->get_countries()),
            new ACP\Editing\Storage\User\Meta($this->get_address_meta_key($config))
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return new ACP\Sorting\Model\User\Meta($this->get_address_meta_key($config));
    }

}