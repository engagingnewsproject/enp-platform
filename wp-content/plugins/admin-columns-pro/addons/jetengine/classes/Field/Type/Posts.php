<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\Multiple;
use ACA\JetEngine\Field\MultipleTrait;
use ACA\JetEngine\Field\RelatedPostTypes;

final class Posts extends Field implements Multiple, RelatedPostTypes
{

    use MultipleTrait;

    public const TYPE = 'posts';

    public function get_related_post_types(): ?array
    {
        return isset($this->settings['search_post_type']) && is_array($this->settings['search_post_type'])
            ? $this->settings['search_post_type']
            : null;
    }

}