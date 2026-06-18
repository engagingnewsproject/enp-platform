<?php

namespace ACP\RequestHandler\Ajax;

use AC\Helper\Select\Response;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\Helper\Select;
use ACP\Helper\Select\User\PaginatedFactory;

class ListScreenUsers implements RequestAjaxHandler
{

    private Nonce\Ajax $nonce;

    public function __construct(Nonce\Ajax $nonce)
    {
        $this->nonce = $nonce;
    }

    public function handle(): void
    {
        $request = new Request();

        if ( ! $this->nonce->verify($request)) {
            wp_send_json_error();
        }

        $include = array_filter(array_map('intval', (array)$request->get('include', [])));

        $options = (new PaginatedFactory())->create([
            'search'  => $request->get('search'),
            'paged'   => $request->get('page', 1),
            'number'  => $include ? count($include) : 20,
            'include' => $include,
        ]);

        $has_more = ! $options->is_last_page();

        $response = new Response($options, $has_more);

        wp_send_json_success($response());
    }
}