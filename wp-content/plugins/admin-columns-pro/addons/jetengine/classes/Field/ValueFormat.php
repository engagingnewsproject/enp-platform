<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

interface ValueFormat
{

    public const FORMAT_ID = 'id';
    public const FORMAT_URL = 'url';
    public const FORMAT_BOTH = 'both';

    public function get_value_format(): string;

}