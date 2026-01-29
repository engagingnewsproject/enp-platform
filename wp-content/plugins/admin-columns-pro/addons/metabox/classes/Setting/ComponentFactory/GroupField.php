<?php

declare(strict_types=1);

namespace ACA\MetaBox\Setting\ComponentFactory;

use AC\Setting\AttributeCollection;
use AC\Setting\AttributeFactory;
use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use AC\Setting\Control\Type\Option;
use ACA\MetaBox\Field\Type\Group;
use ACA\MetaBox\MetaboxFieldTypes;

class GroupField extends BaseComponentFactory
{

    private $field;

    public function __construct(Group $field)
    {
        $this->field = $field;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Group Field', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        $options = $this->get_sub_fields();
        $default_option = $options->first();
        $default = $default_option ? $default_option->get_value() : '';

        return Input\OptionFactory::create_select(
            'group_field',
            $options,
            $config->get('group_field', $default),
            null,
            null,
            new AttributeCollection([
                AttributeFactory::create_refresh(),
            ])
        );
    }

    private function get_sub_fields(): OptionCollection
    {
        $options = new OptionCollection();

        foreach ($this->field->get_sub_fields() as $field) {
            if ($field->get_type() !== MetaboxFieldTypes::GROUP) {
                $options->add(new Option($field->get_name(), $field->get_id()));
            }
        }

        return $options;
    }

}