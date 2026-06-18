<?php

declare(strict_types=1);

namespace ACA\WC\RequestHandler\Ajax;

use AC\Capabilities;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Response\Json;

class OrderMetaFields implements RequestAjaxHandler
{

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();
        $response = new Json();

        if ( ! (new Nonce\Ajax())->verify($request)) {
            $response->error();
        }

        $response->set_header(
            'Cache-Control',
            'max-age=120'
        );

        $response
            ->set_parameter('options', $this->get_meta_field_options())
            ->success();
    }

    private function get_meta_field_options(): array
    {
        global $wpdb;

        $options = $wpdb->get_col(
            "SELECT DISTINCT(meta_key) FROM {$wpdb->prefix}wc_orders_meta ORDER BY meta_key DESC"
        );

        $encoded = [];

        foreach ($options as $option) {
            $encoded[] = [
                'value' => $option,
                'label' => $option,
                'group' => 0 === strpos($option, '_')
                    ? __('Hidden', 'codepress-admin-columns')
                    : __('Public', 'codepress-admin-columns'),
            ];
        }

        return $encoded;
    }

}