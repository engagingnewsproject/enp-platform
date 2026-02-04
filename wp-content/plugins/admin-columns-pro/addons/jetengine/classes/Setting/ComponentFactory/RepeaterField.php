<?php

declare(strict_types=1);

namespace ACA\JetEngine\Setting\ComponentFactory;

use AC\Setting\ComponentFactory\BaseComponentFactory;
use AC\Setting\Config;
use AC\Setting\Control\Input;
use AC\Setting\Control\OptionCollection;
use ACA\JetEngine\Field\Type\Repeater;

class RepeaterField extends BaseComponentFactory
{

    private Repeater $field;

    public function __construct(Repeater $field)
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
            'sub_field',
            OptionCollection::from_array($this->get_repeater_fields()),
            $config->get('sub_field')
        );
    }

    private function get_repeater_fields(): array
    {
        $options = [];

        foreach ($this->field->get_repeated_fields() as $field) {
            $options[$field->get_name()] = $field->get_title();
        }

        return $options;
    }

}