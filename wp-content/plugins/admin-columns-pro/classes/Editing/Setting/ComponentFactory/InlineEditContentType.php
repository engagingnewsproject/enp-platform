<?php

namespace ACP\Editing\Setting\ComponentFactory;

use AC\Expression\StringComparisonSpecification;
use AC\Setting\Children;
use AC\Setting\ComponentCollection;
use AC\Setting\Config;

class InlineEditContentType extends InlineEdit
{

    private $editable_type;

    public function __construct(EditableType $editable_type)
    {
        parent::__construct();

        $this->editable_type = $editable_type;
    }

    protected function get_children(Config $config): ?Children
    {
        $children = parent::get_children($config);

        $components = $children ? $children->get_iterator() : new ComponentCollection();
        $components->add($this->editable_type->create($config, StringComparisonSpecification::equal('on')));

        return new Children($components, true);
    }

}