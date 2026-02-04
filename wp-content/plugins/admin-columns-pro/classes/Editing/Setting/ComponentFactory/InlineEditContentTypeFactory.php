<?php

namespace ACP\Editing\Setting\ComponentFactory;

final class InlineEditContentTypeFactory

{

    public function create(EditableType $editable_type): InlineEditContentType
    {
        return new InlineEditContentType($editable_type);
    }

}