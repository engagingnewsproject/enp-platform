<?php

namespace ACP\Editing\Setting\ComponentFactory;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\Input\OptionFactory;

class BulkEdit extends BaseComponentFactory
{

    protected function get_label(Config $config): ?string
    {
        return __('Enable Bulk Editing', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return OptionFactory::create_toggle(
            'bulk_edit',
            null,
            $config->has('bulk_edit') ? $config->get('bulk_edit') : 'on'
        );
    }

    protected function get_attributes(Config $config, AttributeCollection $attributes): AttributeCollection
    {
        return new AttributeCollection([
            AttributeFactory::create_help_reference('doc-bulk-editing'),
        ]);
    }

}