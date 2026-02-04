<?php

namespace ACP\Sorting\Model\Media;

use ACP\Sorting\FormatValue;
use ACP\Sorting\Model\Post\MetaFormat;
use ACP\Sorting\Type\DataType;

class Width extends MetaFormat
{

    public function __construct()
    {
        parent::__construct(
            new FormatValue\Width(),
            '_wp_attachment_metadata',
            new DataType(DataType::NUMERIC)
        );
    }

}