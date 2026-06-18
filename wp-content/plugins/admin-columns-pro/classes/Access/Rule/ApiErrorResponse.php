<?php

namespace ACP\Access\Rule;

use ACP;
use ACP\Access\Permissions;
use ACP\Access\Rule;
use WP_Error;

/**
 * Set permissions when the API call returns an error.
 * Business errors (e.g. activation limit reached, subscription expired) may include
 * fallback permissions in `data.permissions` - those are used when present.
 * HTTP transport errors (no connectivity, blocked requests) fall back to usage permission.
 */
class ApiErrorResponse implements Rule
{

    protected ACP\API\Response $response;

    public function __construct(ACP\API\Response $response)
    {
        $this->response = $response;
    }

    public function modify(Permissions $permissions): Permissions
    {
        if ( ! $this->response->has_error()) {
            return $permissions;
        }

        // Business error: use the API-provided fallback permissions when available.
        $data = $this->response->get('data');

        if (is_array($data) && is_array($data['permissions'] ?? null)) {
            return new Permissions($data['permissions']);
        }

        // HTTP transport error: grant usage permission as a connectivity fallback.
        if ($this->has_http_error_code($this->response->get_error())) {
            return $permissions->with_usage_permission();
        }

        return $permissions;
    }

    /**
     * @see WP_Http
     */
    private function has_http_error_code(WP_Error $error): bool
    {
        $http_error_codes = [
            'invalid_response', // Invalid response from the server
            'server_unreachable', // Server is unreachable
            'http_failure', // no HTTP transports available
            'http_request_not_executed', // User has blocked requests through HTTP
            'http_request_failed', // any HTTP exceptions
        ];

        return ! empty(array_intersect($error->get_error_codes(), $http_error_codes));
    }

}