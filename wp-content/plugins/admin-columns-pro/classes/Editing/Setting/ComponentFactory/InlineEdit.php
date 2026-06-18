<?php

namespace ACP\Editing\Setting\ComponentFactory;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\Children;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;

class InlineEdit extends BaseComponentFactory
{

    private ?Children $children;

    public function __construct(?Children $children = null)
    {
        $this->children = $children;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Enable Editing', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_toggle('edit', null, $config->has('edit') ? $config->get('edit') : 'on');
    }

    protected function get_children(Config $config): ?Children
    {
        return $this->children;
    }

    protected function get_attributes(Config $config, AttributeCollection $attributes): AttributeCollection
    {
        return new AttributeCollection([
            AttributeFactory::create_help_reference('doc-inline-edit'),
        ]);
    }

}