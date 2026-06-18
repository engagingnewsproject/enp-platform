<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\ShopOrder\Address;

use AC\FormatterCollection;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;
use AC\Setting\DefaultSettingsBuilder;
use ACA\WC\ColumnFactory\WooCommerceGroupTrait;
use ACA\WC\Editing;
use ACA\WC\Search;
use ACA\WC\Setting\ComponentFactory\AddressProperty;
use ACA\WC\Sorting;
use ACA\WC\Type\AddressType;
use ACA\WC\Value\Formatter;
use ACP;
use ACP\Column\FeatureSettingBuilderFactory;

abstract class Address extends ACP\Column\AdvancedColumnFactory
{

    use ACP\ConditionalFormat\FilteredHtmlFormatTrait;
    use WooCommerceGroupTrait;

    private AddressProperty $address_property;

    public function __construct(
        FeatureSettingBuilderFactory $feature_settings_builder_factory,
        DefaultSettingsBuilder $default_settings_builder,
        AddressProperty $address_property
    ) {
        parent::__construct($feature_settings_builder_factory, $default_settings_builder);
        $this->address_property = $address_property;
    }

    abstract protected function get_address_type(): AddressType;

    private function get_address_property(Config $config): string
    {
        return $config->get(AddressProperty::NAME, '');
    }

    protected function get_settings(Config $config): ComponentCollection
    {
        return parent::get_settings($config)->add($this->address_property->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config)->add(
            new Formatter\Order\OrderAddress(
                $this->get_address_type(),
                $this->get_address_property($config)
            )
        );

        if ($this->get_address_property($config) === 'country') {
            $formatters->add(new Formatter\WcFormattedCountry());
        }

        if ($this->get_address_property($config) === 'state') {
            $formatters->add(new Formatter\WcFormattedState($this->get_address_type()));
        }

        return $formatters;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new Search\ShopOrder\AddressFactory())->create(
            $this->get_address_property($config),
            $this->get_address_type()
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new Sorting\ShopOrder\AddressFactory())->create(
            $this->get_address_property($config),
            $this->get_address_type()
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return (new Editing\ShopOrder\AddressFactory())->create(
            $this->get_address_property($config),
            $this->get_address_type()
        );
    }

}