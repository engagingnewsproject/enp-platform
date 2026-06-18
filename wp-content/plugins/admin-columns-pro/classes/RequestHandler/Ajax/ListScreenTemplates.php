<?php

namespace ACP\RequestHandler\Ajax;

use AC\Capabilities;
use AC\ListScreenRepository\Filter;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use AC\Type\Uri;
use AC\Type\Url\Preview;
use ACP\Helper\Select;
use ACP\ListScreenRepository\TemplateJsonFile;
use ACP\Tools\Encode\ListScreenEncoder;

class ListScreenTemplates implements RequestAjaxHandler
{

    private Nonce\Ajax $nonce;

    private ListScreenEncoder $encoder;

    private TemplateJsonFile $template_storage;

    public function __construct(TemplateJsonFile $template_storage, Nonce\Ajax $nonce, ListScreenEncoder $encoder)
    {
        $this->nonce = $nonce;
        $this->encoder = $encoder;
        $this->template_storage = $template_storage;
    }

    public function handle(): void
    {
        if ( ! current_user_can(Capabilities::MANAGE)) {
            return;
        }

        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        $is_network = $request->get('is_network', 'false') === 'true';

        wp_send_json_success([
            'list_tables' => $this->get_table_data($is_network),
        ]);
    }

    private function get_table_data(bool $network = false): array
    {
        $data = [];

        $filter = $network
            ? new Filter\Network()
            : new Filter\Site();

        $sources = $this->template_storage->get_sources();

        foreach ($filter->filter($this->template_storage->find_all()) as $list_screen) {
            $id = $list_screen->get_id();

            $encoded = $this->encoder->encode(
                $list_screen,
                $sources->contains($id) ? $sources->get($id) : null,
                __('File', 'codepress-admin-columns')
            );

            $encoded['url_preview'] = (string)new Preview(new Uri($encoded['view_url']));

            $data[] = $encoded;
        }

        // Sort
        usort($data, function ($a, $b) {
            return [$a['table'], $a['label']] <=> [$b['table'], $b['label']];
        });

        return $data;
    }

}