<?php

namespace ACP\Editing\View;

use ACP\Editing\View;
use ACP\Editing\View\CompositeField\CompositeField;

class Composite extends View
{

    public function __construct(string $layout = 'horizontal')
    {
        parent::__construct('composite');

        $this->set('layout', $layout);
    }

    public function add_field(CompositeField $field): self
    {
        $fields = (array)$this->get_arg('fields');
        $fields[] = $field->get_args();

        $this->set('fields', $fields);

        return $this;
    }

}