<?php

namespace ACP\Access\Rule;

use ACP;
use ACP\Access\Permissions;
use ACP\Access\Rule;

/**
 * Apply permissions based on the API permissions received.
 */
class ApiPermissionResponse implements Rule
{

    protected ACP\API\Response $response;

    public function __construct(ACP\API\Response $response)
    {
        $this->response = $response;
    }

    public function modify(Permissions $permissions): Permissions
    {
        // overwrite permissions with the ones received from the API if available
        $api_permissions = $this->response->get('permissions');

        if (is_array($api_permissions)) {
            return new Permissions($api_permissions);
        }

        return $permissions;
    }

}