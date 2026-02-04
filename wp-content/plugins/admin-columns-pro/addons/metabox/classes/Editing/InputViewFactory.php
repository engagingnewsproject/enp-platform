<?php

declare(strict_types=1);

namespace ACA\MetaBox\Editing;

use ACA\MetaBox\Field;
use ACA\MetaBox\MetaboxFieldTypes;
use ACP;

class InputViewFactory
{

    private function populate_view(ACP\Editing\View $view, Field\Field $field)
    {
        if ($view instanceof ACP\Editing\View\Placeholder && $field instanceof Field\Placeholder) {
            $view->set_placeholder($field->get_placeholder());
        }

        return $view;
    }

    private function create_multi_input(Field\Field $field): ACP\Editing\View
    {
        $mapping = [
            MetaboxFieldTypes::EMAIL       => 'email',
            MetaboxFieldTypes::URL         => 'url',
            MetaboxFieldTypes::OEMBED      => 'url',
            MetaboxFieldTypes::COLORPICKER => 'color',
            MetaboxFieldTypes::TEXTAREA    => 'textarea',
            MetaboxFieldTypes::WYSIWYG     => 'textarea',
        ];

        $subtype = array_key_exists($field->get_type(), $mapping)
            ? $mapping[$field->get_type()]
            : 'text';

        return (new ACP\Editing\View\MultiInput())->set_sub_type($subtype);
    }

    public function create(Field\Field $field): ACP\Editing\View
    {
        if ($field->is_cloneable()) {
            return $this->create_multi_input($field);
        }

        switch ($field->get_type()) {
            case MetaboxFieldTypes::EMAIL:
                return $this->populate_view(new ACP\Editing\View\Email(), $field);
            case MetaboxFieldTypes::URL:
                return $this->populate_view(new ACP\Editing\View\Url(), $field);
            case MetaboxFieldTypes::COLORPICKER:
                return $this->populate_view(new ACP\Editing\View\Color(), $field);
            case MetaboxFieldTypes::TEXTAREA:
                return $this->populate_view(new ACP\Editing\View\TextArea(), $field);
            case MetaboxFieldTypes::TEXT:
            default:
                return $this->populate_view(new ACP\Editing\View\Text(), $field);
        }
    }

}