<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\User;

use AC\Formatter\User\Meta;
use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Setting\ComponentFactory\AddressProperty;
use ACA\WC\Setting\ComponentFactory\User\AddressType;
use ACA\WC\Type;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\AdvancedColumnFactory;
use ACP\Column\FeatureSettingBuilderFactory;
use ACP\ConditionalFormat\ConditionalFormatTrait;
use LogicException;

class AddressFactory extends AdvancedColumnFactory
{

    use ConditionalFormatTrait;
    use WooCommerceGroupTrait;

    private AddressType $address_type;

    private AddressProperty $address_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        AddressType $address_type,
        AddressProperty $address_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->address_type = $address_type;
        $this->address_property = $address_property;
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)
                     ->add($this->address_type->create($config))
                     ->add($this->address_property->create($config));
    }

    public function get_label(): string
    {
        return __('Address', 'woocommerce');
    }

    public function get_column_type(): string
    {
        return 'column-wc-user-address';
    }

    private function is_meta_property(string $property): bool
    {
        $valid_meta_keys = [
            'first_name',
            'last_name',
            'full_name',
            'company',
            'address_1',
            'address_2',
            'city',
            'postcode',
            'country',
            'state',
            'email',
            'phone',
        ];

        return in_array($property, $valid_meta_keys, true);
    }

    private function get_address_type(Config $config): Type\AddressType
    {
        return new Type\AddressType($config->get(AddressType::NAME, 'billing'));
    }

    private function get_address_property(Config $config): string
    {
        return $config->get(AddressProperty::NAME, '');
    }

    private function get_address_meta_key(Config $config): string
    {
        $property = $this->get_address_property($config);

        if ( ! $this->is_meta_property($property)) {
            throw new LogicException('No meta key found for property');
        }

        return sprintf('%s_%s', $this->get_address_type($config), $property);
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config);

        $address_property = $config->get(AddressProperty::NAME, '');

        if ($this->is_meta_property($address_property)) {
            $formatters->add(new Meta($this->get_address_meta_key($config)));
        }

        switch ($address_property) {
            case '' :
                return $formatters->add(
                    new Formatter\User\FullAddress(
                        new Type\AddressType($config->get(AddressType::NAME, 'billing'))
                    )
                );
            case 'country' :
                return $formatters->add(new Formatter\WcFormattedCountry());
            default :
                return $formatters;
        }
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        if ($this->is_meta_property($this->get_address_property($config))) {
            return new ACP\Search\Comparison\Meta\Text($this->get_address_meta_key($config));
        }

        return null;
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        if ( ! $this->is_meta_property($this->get_address_property($config))) {
            return new Editing\User\Address($this->get_address_type($config));
        }

        switch ($this->get_address_property($config)) {
            case 'country' :
                $countries = WC()->countries;
                $options = $countries
                    ? array_merge(['' => __('None', 'codepress-admin-columns')], $countries->get_countries())
                    : [];

                return new ACP\Editing\Service\Basic(
                    new ACP\Editing\View\Select($options),
                    new ACP\Editing\Storage\User\Meta($this->get_address_meta_key($config))
                );

            default :
                return new ACP\Editing\Service\Basic(
                    new ACP\Editing\View\Text(),
                    new ACP\Editing\Storage\User\Meta($this->get_address_meta_key($config))
                );
        }
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        if ( ! $this->is_meta_property($this->get_address_property($config))) {
            return null;
        }

        return new ACP\Sorting\Model\User\Meta($this->get_address_meta_key($config));
    }

}