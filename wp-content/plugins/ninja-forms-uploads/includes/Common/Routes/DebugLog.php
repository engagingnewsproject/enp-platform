<?php

namespace NinjaForms\FileUploads\Common\Routes;

if (!defined('ABSPATH')) exit;

use NinjaForms\FileUploads\Common\Interfaces\NfLogHandler;
use NinjaForms\FileUploads\Common\Handlers\DownloadDebugLog;
use \WP_REST_Request;

/**
 * 
 */
class DebugLog
{
    const GETLOGSENDPOINT = 'debug-log/get-all';
    const DELETELOGSENDPOINT = 'debug-log/delete-all';
    
    /**
     * Route upon which endpoints are built
     *
     * @var string
     */
    protected $route;

    /** @var NfLogHandler */
    protected $logHandler;

    /** @var DownloadDebugLog */
    protected $downloadDebugLog;

    public function __construct(NfLogHandler $logHandler, string $route, DownloadDebugLog $downloadDebugLog)
    {
        $this->logHandler = $logHandler;
        $this->route = $route;
        $this->downloadDebugLog = $downloadDebugLog;

        \add_action('rest_api_init', [$this, 'registerRoutes']);
    }
    /**
     * Register REST API routes for the debug log
     */
    function registerRoutes()
    {
        \register_rest_route($this->route, self::GETLOGSENDPOINT, array(
            'methods' => 'POST',
            'args' => [],
            'callback' => [$this, 'getDebugLogEntries'],
            'permission_callback' => [$this, 'getDebugLogEntriesPermissionCallback']
        ));

        \register_rest_route($this->route, self::DELETELOGSENDPOINT, array(
            'methods' => 'POST',
            'args' => [],
            'callback' => [$this, 'deleteAllDiagnostics'],
            'permission_callback' => [$this, 'getDebugLogEntriesPermissionCallback']
        ));
    }

    public function getDebugLogEntries(WP_REST_Request $request)
    {
        $response = [
            'data'=>$this->downloadDebugLog->getDebugJson()
        ];

        return \rest_ensure_response($response);
    }

    public function deleteAllDiagnostics(WP_REST_Request $request)
    {
        $this->logHandler->deletePluginLogEntries();

        $result = __('Request made to delete all plugin log entries', 'ninja-forms-uploads');

        $response = [
            'result' => $result
        ];

        return \rest_ensure_response($response);
    }

    /**
     * Verify user is permitted to download diagnostics
     *
     * @param WP_REST_Request $request
     * @return void
     */
    public function getDebugLogEntriesPermissionCallback(WP_REST_Request $request)
    {
        return true;
    }
}
