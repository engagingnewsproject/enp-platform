<?php

declare(strict_types=1);

namespace ACA\SeoPress\ColumnFactory;

trait GroupTrait
{

    protected function get_group(): ?string
    {
        return 'seopress';
    }
}