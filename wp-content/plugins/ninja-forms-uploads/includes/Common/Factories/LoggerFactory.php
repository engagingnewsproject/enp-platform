<?php

namespace NinjaForms\FileUploads\Common\Factories;

use NinjaForms\FileUploads\Common\Handlers\Logger;
use NinjaForms\FileUploads\Common\Handlers\TransientLogHandler;
use NinjaForms\FileUploads\Common\Handlers\TableLogHandler;

use NinjaForms\FileUploads\Common\Interfaces\LoggerFactory as InterfacesLoggerFactory;
use NinjaForms\FileUploads\Common\Interfaces\NfLogger;
use NinjaForms\FileUploads\Common\Interfaces\NfLogHandler;
use NinjaForms\FileUploads\Common\Entities\LogLevel;
use NinjaForms\FileUploads\Common\Handlers\DownloadDebugLog;
use NinjaForms\FileUploads\Common\Routes\DebugLog;


class LoggerFactory implements InterfacesLoggerFactory{

    /**
     * Plugin prefix for uniquely identifying logs from a given plugin
     *
     * @var string
     */
    protected $pluginPrefix ;

    /** @var bool */
    protected $isDebugOn;

    public function __construct(string $pluginPrefix, ?bool $isDebugOn=false)
    {
        $this->pluginPrefix = $pluginPrefix;
        $this->isDebugOn = $isDebugOn;
    }

    /** @inheritDoc */
    public function getLogger( ): NfLogger
    {
        $logger = $this->constructLogger();

        if($this->isDebugOn){

            // temporarily use transient log handler to record debug logs
            $debugHandler = $this->getDebugLogHandler();

            // Assign debug handler to handle DEBUG level log requests
            $logger->pushLogHandler($debugHandler, LogLevel::DEBUG);
        }

        $logger->pushLogHandler($this->getWarningHandler(),LogLevel::WARNING);
        
        return $logger;
    }

    /** @inheritDoc     */
    public function getDebugLogHandler(): NfLogHandler
    {
        // $debugHandler = (new TransientLogHandler())->setPluginPrefix($this->pluginPrefix);
        $debugHandler = (new TableLogHandler())->setPluginPrefix($this->pluginPrefix);

        return $debugHandler;
    }

    /** @inheritDoc */
    public function getWarningHandler(): NfLogHandler
    {
        // $debugHandler = (new TransientLogHandler())->setPluginPrefix($this->pluginPrefix);
        $debugHandler = (new TableLogHandler())->setPluginPrefix($this->pluginPrefix);

        return $debugHandler;
    }

    /** @inheritDoc */
    public function createDebugLogRoutes( string $logRoute): DebugLog{

        $handler = $this->getDebugLogHandler();
        $downloadDebugLog = new DownloadDebugLog($handler);
        $debugLogRoute = new DebugLog($handler, $logRoute, $downloadDebugLog);

        return $debugLogRoute;
    }

    /**
     * Construct common logger
     *
     * @return NfLogger
     */
    protected function constructLogger( ): NfLogger
    {
        $logger = new Logger();

        return $logger;
    }

}