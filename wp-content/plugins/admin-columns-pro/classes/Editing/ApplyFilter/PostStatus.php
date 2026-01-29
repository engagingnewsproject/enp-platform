<?php

namespace ACP\Editing\ApplyFilter;

use AC\Column\Context;

class PostStatus
{

    private Context $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function apply_filters(array $stati, string $post_type): array
    {
        return (array)apply_filters('ac/editing/post_statuses', $stati, $this->context, $post_type);
    }

}