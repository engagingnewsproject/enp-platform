<?php

declare(strict_types=1);

namespace ACP\Formatter\Comment;

use AC;
use AC\Type\Value;

class PostType implements AC\Formatter
{

    public function format(Value $value)
    {
        return $value->with_value(get_post_type(get_comment($value->get_id())->comment_post_ID));
    }

}