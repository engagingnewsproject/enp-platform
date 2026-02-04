<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface PostTypeFilterable
{

    public function get_post_types(): array;

}