<?php

declare(strict_types=1);

namespace ACA\WC\ColumnFactory\Order\Address;

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

abstract class AddressFactory extends ACP\Column\AdvancedColumnFactory
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
        return parent::get_settings($config)
                     ->add($this->address_property->create($config));
    }

    protected function get_formatters(Config $config): FormatterCollection
    {
        $formatters = parent::get_formatters($config)
                            ->add(
                                new Formatter\Order\OrderAddress(
                                    $this->get_address_type(),
                                    $this->get_address_property($config)
                                )
                            );

        switch ($this->get_address_property($config)) {
            case 'state':
                $formatters->add(new Formatter\WcFormattedState($this->get_address_type()));
                break;
            case 'country':
                $formatters->add(new Formatter\WcFormattedCountry());
                break;
        }

        return $formatters;
    }

    protected function get_search(Config $config): ?ACP\Search\Comparison
    {
        return (new Search\Order\AddressesComparisonFactory($this->get_address_type()))->create(
            $this->get_address_property($config)
        );
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new Sorting\Order\AddressesFactory($this->get_address_type()))->create(
            $this->get_address_property($config)
        );
    }

    protected function get_editing(Config $config): ?ACP\Editing\Service
    {
        return (new Editing\Order\AddressServiceFactory($this->get_address_type()))->create(
            $this->get_address_property($config)
        );
    }

}