<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field\Type;

use ACA\MetaBox\Field;

class FileUpload extends Field\Field implements Field\Multiple
{

    public function is_multiple(): bool
    {
        return true;
    }

}