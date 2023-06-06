<?php

namespace NinjaForms\FileUploads\Common\Handlers;

use NinjaForms\FileUploads\Common\Interfaces\NfLogHandler;
use NinjaForms\FileUploads\Common\Entities\LogEntry;

if (!defined('ABSPATH')) exit;

/**
 * 
 */
class DownloadDebugLog
{
    /** @var NfLogHandler */
    protected $logHandler;

    protected $format = 'json';
    protected $delimiter = ',';
    protected $open = '[';
    protected $close = ']';
    protected $terminator = "\n";

    /**
     * Filepath of downloaded file in default directory
     *
     * @param string
     */
    protected $file_path = '';

    /**
     * File URL of downloaded file in default directory
     *
     * @param string
     */
    protected $fileUrl = '';


    public function __construct(NfLogHandler $logHandler)
    {
        $this->logHandler = $logHandler;
    }

    /**
     * Get debug log as JSON string
     *
     * @return string
     */
    public function getDebugJson(): string
    {
        $logEntriesCollection = $this->getDebugLog();
        $return = $this->constructJsonString($logEntriesCollection);
        return $return;
    }

    /**
     * Get collection of log entries for the plugin
     *
     * @return array
     */
    protected function getDebugLog(): array
    {
        $return = $this->logHandler->getPluginLogEntries();

        return $return;
    }

    /**
     * Construct JSON string from debug log
     *
     * @return string
     */
    protected function constructJsonString(array $logEntriesCollection): string
    {
        $return = $this->open;

        foreach ($logEntriesCollection as $logEntry) {

            $return .= $this->constructLineItem($logEntry) . $this->delimiter;
        }

        $return .= $this->close;

        return $return;
    }



    /**
     * Construct a stringed line item as JSON for easy reading
     * 
     * Unpack double encoded supportingData
     *
     * @param LogEntry $logEntry
     * @return string
     */
    protected function constructLineItem(LogEntry $logEntry): string
    {
        $supportingDataString = $logEntry->getSupportingData();

        $array = $logEntry->toArray();
        $array['supportingData'] = json_decode($supportingDataString, true);

        $json = json_encode($array);
        return $json;
    }

}
