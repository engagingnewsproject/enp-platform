<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field;

interface RelatedPostTypes
{

    /**
     * @return string[]|null
     */
    public function get_related_post_types(): ?array;

}