<?php

declare(strict_types=1);

namespace ACA\SeoPress\TableScreen;

use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\TableId;
use WP_Screen;

class RedirectFactory implements TableScreenFactory
{

    public const POST_TYPE = 'seopress_404';

    public function create_table_screen(): Redirect
    {
        return new Redirect(
            get_post_type_object(self::POST_TYPE)
        );
    }

    public function create(TableId $id): TableScreen
    {
        return $this->create_table_screen();
    }

    public function can_create(TableId $id): bool
    {
        return $id->equals(new TableId(self::POST_TYPE));
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return $this->create_table_screen();
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return 'edit' === $screen->base
               && self::POST_TYPE === $screen->post_type
               && 'edit-' . $screen->post_type === $screen->id;
    }

}