<?php

declare(strict_types=1);

namespace ACA\ACF\Field\Type;

use ACA\ACF\Field;

class GroupSubField extends Field
{

    protected Field $sub_field;

    protected Field $group;

    public function __construct(array $settings, Field $group, Field $sub_field)
    {
        parent::__construct($settings);

        $this->sub_field = $sub_field;
        $this->group = $group;
    }

    public function get_sub_field(): Field
    {
        return $this->sub_field;
    }

    public function get_group_field(): Field
    {
        return $this->group;
    }

    public function get_meta_key(): string
    {
        return sprintf('%s_%s', $this->group->get_meta_key(), $this->sub_field->get_meta_key());
    }

    public function get_label(): string
    {
        return sprintf(
            '%s - %s',
            $this->group->get_label() ?: 'Group',
            $this->sub_field->get_label() ?: $this->sub_field->get_meta_key()
        );
    }

}