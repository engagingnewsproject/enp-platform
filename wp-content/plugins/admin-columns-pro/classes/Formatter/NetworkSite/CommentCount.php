<?php

namespace ACP\Formatter\NetworkSite;

use AC\Formatter;
use AC\Type\Value;

class CommentCount implements Formatter
{

    private string $comment_status;

    public function __construct(string $comment_status)
    {
        $this->comment_status = $comment_status;
    }

    public function format(Value $value): Value
    {
        $count = (object)get_comment_count();

        return $value->with_value($count->{$this->comment_status} ?? 0);
    }

}