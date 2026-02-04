<?php

namespace ACP\Export\Strategy;

use AC\Type\ValueCollection;
use ACP\Export\Exporter\TableDataFactory;
use ACP\Export\ResponseFactory;
use ACP\Export\Strategy;
use WP_Comment_Query;

class Comment extends Strategy
{

    private TableDataFactory $table_data_factory;

    private ResponseFactory $response_factory;

    public function __construct(TableDataFactory $table_data_factory, ResponseFactory $response_factory)
    {
        $this->table_data_factory = $table_data_factory;
        $this->response_factory = $response_factory;
    }

    public function handle_export(): void
    {
        add_action('parse_comment_query', [$this, 'comments_query'], PHP_INT_MAX - 100);
    }

    /**
     * Catch the comments query and run it with altered parameters for pagination. This should be
     * attached to the parse_comment_query hook when an AJAX request is sent
     */
    public function comments_query($query): void
    {
        if ($query->query_vars['count']) {
            return;
        }

        remove_action('parse_comment_query', [$this, __FUNCTION__], PHP_INT_MAX - 100);

        $per_page = $this->items_per_iteration;

        $query->query_vars['offset'] = $this->counter * $per_page;
        $query->query_vars['number'] = $per_page;
        $query->query_vars['fields'] = 'ids';

        $ids = $this->ids;

        if ($ids) {
            $query->query_vars['comment__in'] = isset($query->query_vars['comment__in'])
                ? array_merge($ids, (array)$query->query_vars['comment__in'])
                : $ids;
        }

        $query = new WP_Comment_Query($query->query_vars);

        $this->response_factory->create(
            $this->table_data_factory->create(
                $this->columns,
                ValueCollection::from_ids(0, $query->get_comments()),
                0 === $this->counter
            )
        );
        exit;
    }

}