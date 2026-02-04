<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use PodsField_Pick;

class ImageSizeFactory extends BasePickFactory
{

    protected function get_options(): array
    {
        return (new PodsField_Pick())->data_image_sizes();
    }

}