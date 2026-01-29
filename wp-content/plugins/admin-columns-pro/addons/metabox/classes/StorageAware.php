<?php

declare(strict_types=1);

namespace ACA\MetaBox;

interface StorageAware
{

    public const META_BOX = 'meta_box';
    public const CUSTOM_TABLE = 'custom_table';

    public function get_storage(): string;

}