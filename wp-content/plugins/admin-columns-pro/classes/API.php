<?php

namespace ACP;

use ACP\API\Request;
use ACP\API\Response;
use WP_Error;

class API
{

    protected ?string $url = null;

    protected ?string $proxy = null;

    protected bool $use_proxy = true;

    private array $meta = [];

    public function get_url(): ?string
    {
        return $this->url;
    }

    public function set_url(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function get_proxy(): ?string
    {
        return $this->proxy;
    }

    public function set_proxy(string $proxy): self
    {
        $this->proxy = $proxy;

        return $this;
    }

    public function is_use_proxy(): bool
    {
        return $this->use_proxy;
    }

    public function set_use_proxy(bool $use_proxy): self
    {
        $this->use_proxy = $use_proxy;

        return $this;
    }

    public function set_request_meta(array $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    public function dispatch(Request $request): Response
    {
        $body = $request->get_body();

        if ( ! isset($body['meta']) || ! is_array($body['meta'])) {
            $body['meta'] = [];
        }

        $body['meta'] = array_merge($body['meta'], $this->meta);

        $request->set_body($body);

        $request->set_header('X-AC-Version', ACP_VERSION);
        $request->set_header('X-AC-Command', $body['command'] ?? '');

        if ($this->use_proxy) {
            $response = $this->send($request, $this->proxy);

            // Return immediately for application errors (body is set = server responded with valid JSON).
            // Only fall through to direct URL for transport errors.
            if ( ! $response->has_error() || $response->get_body() !== null) {
                return $response;
            }
        }

        return $this->send($request, $this->url);
    }

    private function send(Request $request, ?string $url): Response
    {
        $response = new Response();
        $data = wp_remote_post($url, $request->get_args());

        if (is_wp_error($data)) {
            return $response->with_error($data);
        }

        $response_code = $data['response']['code'] ?? 0;

        // Invalid response code.
        if ($response_code < 200 || $response_code >= 400) {
            return $response->with_error(
                new WP_Error(
                    'server_unreachable',
                    __('The Admin Columns Server was unreachable.', 'codepress-admin-columns')
                )
            );
        }

        $body = wp_remote_retrieve_body($data);
        $decoded = json_decode($body, true);

        // Invalid JSON response.
        if (empty($decoded)) {
            return $response->with_error(
                new WP_Error(
                    'invalid_response',
                    'Invalid response.'
                )
            );
        }

        // Only set body when response is valid.
        $response = $response->with_body((object)$decoded);

        // Response is OK, but te body contains an error. Valid API response.
        if ($response->get('error')) {
            $response = $response->with_error(
                new WP_Error($response->get('code'), $response->get('message'))
            );
        }

        return $response;
    }

}