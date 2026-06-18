<?php

declare(strict_types=1);

namespace ACA\ACF\Setting\ComponentFactory;

use AC;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use ACA\ACF\Field;

class RepeaterSubField extends AC\Setting\ComponentFactory\BaseComponentFactory
{

    public const NAME = 'sub_field';

    private Field $field;

    public function __construct(Field $field)
    {
        $this->field = $field;
    }

    protected function get_label(Config $config): ?string
    {
        return __('Subfield', 'codepress-admin-columns');
    }

    protected function get_input(Config $config): ?Input
    {
        return Input\OptionFactory::create_select(
            self::NAME,
            $this->get_sub_fields_options(),
            $config->get(self::NAME, ''),
            null,
            null,
            new AC\Setting\AttributeCollection([
                AC\Setting\AttributeFactory::create_refresh(),
            ])
        );
    }

    private function get_sub_fields_options(): OptionCollection
    {
        $options = [];

        if ( ! $this->field instanceof Field\Subfields) {
            return OptionCollection::from_array($options);
        }

        foreach ($this->field->get_sub_fields() as $sub_field) {
            $options[$sub_field['key']] = $sub_field['label'];
        }

        return OptionCollection::from_array($options);
    }

}