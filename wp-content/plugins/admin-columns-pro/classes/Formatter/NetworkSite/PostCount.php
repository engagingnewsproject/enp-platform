<?php

namespace ACP\Formatter\NetworkSite;

use AC\Formatter;
use AC\Type\Value;

class PostCount implements Formatter
{

    private string $post_type;

    private string $post_status;

    public function __construct(string $post_type, string $post_status)
    {
        $this->post_type = $post_type;
        $this->post_status = $post_status;
    }

    public function format(Value $value): Value
    {
        global $wpdb;

        $blog_id = (int)$value->get_id();
        $table = $wpdb->get_blog_prefix($blog_id) . 'posts';
        $post_status = $this->post_status;

        $sql = "SELECT count(*) FROM {$table}";

        $conditional = [];

        // Exclude internal post status, like 'auto-draft' and 'inherit' or 'trash'
        if ($excluded = $this->get_exludeded_post_statuses()) {
            $conditional[] = "{$table}.post_status NOT IN ( '" . implode("','", $excluded) . "' )";

            $post_status = '';
        }

        if ($this->post_type) {
            $conditional[] = $wpdb->prepare("{$table}.post_type = %s", $this->post_type);
        }

        if ($post_status) {
            $conditional[] = $wpdb->prepare("{$table}.post_status = %s", $post_status);
        }

        if ($conditional) {
            $sql .= " WHERE " . implode(" AND ", $conditional);
        }

        $new_value = $wpdb->get_var($sql);

        if ($this->post_type) {
            $url = add_query_arg(['post_type' => $this->post_type], get_admin_url($blog_id, 'edit.php'));

            if ($post_status) {
                $url = add_query_arg(['post_status' => $post_status], $url);
            }

            $new_value = ac_helper()->html->link($url, (string)$new_value);
        }

        return $value->with_value($new_value);
    }

    private function get_exludeded_post_statuses(): array
    {
        if ('without_trash' === $this->post_status) {
            return get_post_stati(['show_in_admin_all_list' => false]);
        }

        if ( ! $this->post_status) {
            return get_post_stati(['show_in_admin_status_list' => false]);
        }

        return [];
    }

}