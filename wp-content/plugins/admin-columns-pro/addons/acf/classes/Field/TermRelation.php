<?php

declare(strict_types=1);

namespace ACA\ACF\Field;

interface TermRelation
{

    public function uses_native_term_relation(): bool;

}