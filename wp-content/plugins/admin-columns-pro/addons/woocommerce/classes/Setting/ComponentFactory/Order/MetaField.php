<?php

declare(strict_types=1);

namespace ACA\WC\Setting\ComponentFactory\Order;

use AC\Setting\Children;
use AC\Setting\ComponentCollection;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\ComponentFactory\FieldType;
use AC\Setting\Config;
use AC\Setting\Control\Input;

class MetaField extends BaseComponentFactory
{

    private $field_type;

    public function __construct(FieldType $field_type)
    {
        $this->field_type = $field_type;
    }

    public const KEY = 'meta_field';

    protected function get_label(Config $config): ?string
    {
        return __('Field', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select_remote(
            self::KEY,
            'ac-wc-order-meta-fields',
            $config->get(self::KEY, ''),
            [],
            __('Select', 'codepress-admin-columns')
        );
    }

    protected function get_children(Config $config): ?Children
    {
        return new Children(new ComponentCollection([
            $this->field_type->create($config),
        ]));
    }

}