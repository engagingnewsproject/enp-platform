<?php

declare(strict_types=1);

namespace ACA\WC\BulkDelete\Deletable;

use ACP;
use ACP\Editing\BulkDelete\Deletable;
use WP_Post_Type;

class Product implements Deletable
{

    private WP_Post_Type $post_type;

    public function __construct(WP_Post_Type $post_type)
    {
        $this->post_type = $post_type;
    }

    public function user_can_delete(): bool
    {
        return current_user_can($this->post_type->cap->delete_posts);
    }

    public function get_delete_request_handler(): RequestHandler\Product
    {
        return new RequestHandler\Product();
    }

    public function get_query_request_handler(): ACP\Editing\RequestHandler\Query\Post
    {
        return new ACP\Editing\RequestHandler\Query\Post();
    }

}