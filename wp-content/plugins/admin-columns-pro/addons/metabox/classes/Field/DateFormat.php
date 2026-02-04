<?php

declare(strict_types=1);

namespace ACA\MetaBox\Field;

interface DateFormat
{

    public function get_date_format(): string;

    public function is_timestamp(): bool;

}