<?php

namespace ACP\Editing\Setting\ComponentFactory;

use AC\Expression\StringComparisonSpecification;
use AC\Setting\Children;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;

class InlineEditCreateTerms extends InlineEdit
{

    private CreateTerms $create_terms;

    public function __construct(CreateTerms $create_terms)
    {
        parent::__construct();

        $this->create_terms = $create_terms;
    }

    protected function get_children(Config $config): ?Children
    {
        $children = parent::get_children($config);

        $components = $children
            ? $children->get_iterator()
            : new ComponentCollection();

        $components->add($this->create_terms->create($config, StringComparisonSpecification::equal('on')));

        return new Children($components, true);
    }

}