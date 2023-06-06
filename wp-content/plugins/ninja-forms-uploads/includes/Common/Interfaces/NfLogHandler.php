<?php

namespace NinjaForms\FileUploads\Common\Interfaces;

interface NfLogHandler
{

    /**
     * Handle the log data in some fashion
     * 
     * Typical uses include storing data in log file or in DB
     *
     * @param string|\Stringable $key
     * @param array $context
     * @return void
     */
    public function log(  $key, array $context = []): void;

    /**
     * Return indexed array of all log entries for a given plugin
     *
     * @return array LogEntry[]
     */
    public function getPluginLogEntries(): array;


    /**
     * Delete all plugin entries for a given plugin
     *
     * @return void
     */
    public function deletePluginLogEntries( ): void;

    /**
     * Set plugin prefix
     *
     * Handlers must have a method to retrieve stored entries by plugin; setting
     * this key enables easy identification by each handler
     *
     * @param string $prefix
     * @return void
     */
    public function setPluginPrefix(string $prefix): NfLogHandler;
}

