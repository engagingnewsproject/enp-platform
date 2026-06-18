<?php

namespace ACP\Filtering\Setting\ComponentFactory;

use AC\Expression\StringComparisonSpecification;
use AC\Setting\Children;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;

class FilteringDate extends Filtering
{

    private FilterDateFormat $filter_date_format;

    public function __construct(FilterLabel $filter_label, FilterDateFormat $filter_date_format)
    {
        parent::__construct($filter_label);

        $this->filter_date_format = $filter_date_format;
    }

    protected function get_children(Config $config): ?Children
    {
        $children = parent::get_children($config);

        $components = $children ? $children->get_iterator() : new ComponentCollection();
        $components->add($this->filter_date_format->create($config, StringComparisonSpecification::equal('on')));

        return new Children($components, true);
    }

}