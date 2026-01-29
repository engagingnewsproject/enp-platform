<?php

namespace ACP\Search\Comparison\Post;

use AC\Setting\ComponentFactory;

class CommentCountFactory
{

    public function create(string $type): CommentCount
    {
        switch ($type) {
            case ComponentFactory\CommentStatus::STATUS_APPROVED :
                return new CommentCount([CommentCount::STATUS_APPROVED]);
            case ComponentFactory\CommentStatus::STATUS_TRASH :
                return new CommentCount([CommentCount::STATUS_TRASH]);
            case ComponentFactory\CommentStatus::STATUS_SPAM :
                return new CommentCount([CommentCount::STATUS_SPAM]);
            case ComponentFactory\CommentStatus::STATUS_PENDING :
                return new CommentCount([CommentCount::STATUS_PENDING]);
            default :
                return new CommentCount([CommentCount::STATUS_APPROVED, CommentCount::STATUS_PENDING]);
        }
    }

}