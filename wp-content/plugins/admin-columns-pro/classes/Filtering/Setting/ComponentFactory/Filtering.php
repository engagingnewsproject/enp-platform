<?php

namespace ACP\Filtering\Setting\ComponentFactory;

use AC\Expression\StringComparisonSpecification;
use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\Children;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;

class Filtering extends BaseComponentFactory
{

    private FilterLabel $filter_label;

    public function __construct(FilterLabel $filter_label)
    {
        $this->filter_label = $filter_label;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Enable Filtering', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_toggle(
            'filter',
            null,
            $config->has('filter') ? $config->get('filter') : 'off',
            new AttributeCollection([
                AttributeFactory::create_refresh(),
            ])
        );
    }

    protected function get_children(Config $config): ?Children
    {
        return new Children(new ComponentCollection([
            $this->filter_label->create($config, StringComparisonSpecification::equal('on')),
        ]), false);
    }

    protected function get_attributes(Config $config, AttributeCollection $attributes): AttributeCollection
    {
        return new AttributeCollection([
            AttributeFactory::create_help_reference('doc-filtering'),
        ]);
    }

}