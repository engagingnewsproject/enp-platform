<?php

namespace ACP\RequestHandler\Ajax;

use AC\IntegrationRepository;
use AC\Nonce;
use AC\Request;
use AC\RequestAjaxHandler;
use ACP\Settings\General\IntegrationStatus;

class IntegrationToggle implements RequestAjaxHandler
{

    private $repository;

    private $integration_status;

    public function __construct(IntegrationRepository $repository, IntegrationStatus $integration_status)
    {
        $this->repository = $repository;
        $this->integration_status = $integration_status;
    }

    public function handle(): void
    {
        $request = new Request();

        if ( ! (new Nonce\Ajax())->verify($request)) {
            wp_send_json_error();
        }

        $integration = $this->repository->find_by_slug(
            $request->get('integration')
        );

        if ( ! $integration) {
            wp_send_json_error();
        }
        
        '1' === $request->get('status')
            ? $this->integration_status->set_active($integration->get_slug())
            : $this->integration_status->set_inactive($integration->get_slug());

        wp_send_json_success();
    }

}