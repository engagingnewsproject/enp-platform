<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce\Ajax;
use AC\Request;
use AC\RequestAjaxHandler;

class ResetSorting implements RequestAjaxHandler
{

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            wp_send_json_error();
        }

        $request = new Request();

        if ( ! (new Ajax())->verify($request)) {
            wp_send_json_error();
        }

        $this->delete_for_all_users();
        wp_send_json_success(['message' => __('All sorting preferences have been reset.', 'codepress-admin-columns')]);
    }

    private function delete_for_all_users(): void
    {
        global $wpdb;

        $key = $wpdb->get_blog_prefix() . 'ac_preferences_sorted_by';

        $sql = "
			DELETE 
			FROM $wpdb->usermeta 
			WHERE meta_key LIKE %s
		";

        $sql = $wpdb->prepare(
            $sql,
            $wpdb->esc_like($key) . '%'
        );

        $wpdb->query($sql);
    }

}