<?php

declare(strict_types=1);

namespace ACA\BeaverBuilder\TableScreen;

use AC\TableScreen;
use AC\TableScreenFactory;
use AC\Type\Labels;
use AC\Type\TableId;
use AC\Type\Url;
use WP_Screen;

class TemplateFactory implements TableScreenFactory
{

    public const POST_TYPE = 'fl-builder-template';

    private string $page;

    private Labels $labels;

    public function __construct(string $page, Labels $labels)
    {
        $this->page = $page;
        $this->labels = $labels;
    }

    public function create_table_screen(): Template
    {
        $url = new Url\ListTable\Post('fl-builder-template');
        $url = $url->with_arg('fl-builder-template-type', $this->page);

        return new Template(
            new TableId(self::POST_TYPE . $this->page),
            'edit-' . self::POST_TYPE,
            $this->labels,
            $url
        );
    }

    public function create(TableId $id): TableScreen
    {
        return $this->create_table_screen();
    }

    public function can_create(TableId $id): bool
    {
        return $id->equals(new TableId(self::POST_TYPE . $this->page));
    }

    public function create_from_wp_screen(WP_Screen $screen): TableScreen
    {
        return $this->create_table_screen();
    }

    public function can_create_from_wp_screen(WP_Screen $screen): bool
    {
        return 'edit' === $screen->base
               && self::POST_TYPE === $screen->post_type
               && 'edit-' . $screen->post_type === $screen->id
               && $this->page === filter_input(INPUT_GET, 'fl-builder-template-type');
    }

}