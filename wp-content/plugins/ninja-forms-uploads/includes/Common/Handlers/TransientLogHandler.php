<?php

namespace NinjaForms\FileUploads\Common\Handlers;

use NinjaForms\FileUploads\Common\Interfaces\NfLogHandler;
use NinjaForms\FileUploads\Common\Entities\LogEntry;

/**
 * Stores temporary record as WP transients
 * 
 * Temporary logs are expected to expire
 */
class TransientLogHandler implements NfLogHandler
{
    /**
     * String used to uniquely identify entries for a given plugin
     *
     * @var string
     */
    protected $pluginPrefix = '';

    /** @inheritDoc */
    public function log(  $message, array $context = []): void
    {
        $logEntry = $this->buildLogEntry($message, $context);

        $this->storeLogEntryAsTransient($logEntry);
    }

    /**
     * Construct log entry object from log request
     *
     * @param string|\Stringable $message
     * @param array $context
     * @return LogEntry
     */
    protected function buildLogEntry(  $message, array $context = []): LogEntry
    {
        $return = LogEntry::fromArray($context);

        if (!empty($message)) {
            $return->setSummary($message);
        }

        return $return;
    }

    /**
     * Store entry as WP transient
     *
     * Removes 'expiration' and 'logKey' values from entry because they are only
     * needed for setting expiration, not actual diagnostics
     *
     * @param LogEntry $logEntry
     * @return void
     */
    protected function storeLogEntryAsTransient(LogEntry $logEntry): void
    {
        $expiration = $logEntry->getExpiration();
        // clear expiration from entry because it is no longer needed
        $logEntry->setExpiration(0);

        $key = $this->pluginPrefix.'_'.$logEntry->getLogPoint();

        \set_transient($key, $logEntry->__toString(), $expiration);
    }

    /** @inheritDoc */
    public function getPluginLogEntries(): array
    {
        global $wpdb;

        $request ="SELECT option_name, option_value FROM $wpdb->options WHERE option_name LIKE '_transient_".$this->pluginPrefix."%'";
        $logEntries = $wpdb->get_results($request);

        $return = [];
        foreach ($logEntries as $optionRecord) {

            $logEntryString = $optionRecord->option_value;
            $return[] = LogEntry::fromString($logEntryString);
        }

        return $return;
    }
    
    /** @inheritDoc */
    public function deletePluginLogEntries( ): void{
        global $wpdb;
        $request ="SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_".$this->pluginPrefix."%'";
        
        $transientKeys = $wpdb->get_results($request);
        
        foreach($transientKeys as $prefixedTransientKeyObject){
            $prefixedTransientKey=$prefixedTransientKeyObject->option_name;
            
            $transientKey = \str_replace('_transient_','',$prefixedTransientKey);
            \delete_transient($transientKey);
        }
    }

    /** @inheritDoc */
    public function setPluginPrefix(string $prefix): NfLogHandler{
        $this->pluginPrefix = $prefix;
        return $this;
    }
}
