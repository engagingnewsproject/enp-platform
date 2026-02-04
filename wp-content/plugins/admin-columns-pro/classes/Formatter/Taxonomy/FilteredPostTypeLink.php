<?php

namespace ACP\Formatter\Taxonomy;

use AC\Formatter;
use AC\Type\Value;

class FilteredPostTypeLink implements Formatter
{

    private string $post_type;

    private string $taxonomy;

    private string $status;

    public function __construct(string $taxonomy, string $post_type = 'any', ?string $status = null)
    {
        $this->post_type = $post_type;
        $this->taxonomy = $taxonomy;
        $this->status = $status ?: 'any';
    }

    public function format(Value $value): Value
    {
        $count = $value->get_value()
            ? number_format_i18n($value->get_value())
            : 0;

        $term = get_term($value->get_id(), $this->taxonomy);

        $args = [
            'post_type'                                => $this->post_type,
            $this->get_taxonomy_param($this->taxonomy) => $term->slug,
        ];

        if ($this->status !== 'any') {
            $args['post_status'] = $this->status;
        }

        $url = add_query_arg(
            $args,
            admin_url('edit.php')
        );

        return $value->with_value(sprintf('<a href="%s">%s</a>', $url, $count));
    }

    private function get_taxonomy_param(string $taxonomy): string
    {
        switch ($taxonomy) {
            case 'category':
                return 'category_name';
            case 'post_tag':
                return 'tag';
            default:
                return $taxonomy;
        }
    }

}