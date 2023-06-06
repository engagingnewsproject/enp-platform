<?php

//TODO: Extract $wpdb interactions into separate class.

namespace NinjaForms\FileUploads\Common\Handlers;

use NinjaForms\FileUploads\Common\Interfaces\NfLogHandler;
use NinjaForms\FileUploads\Common\Entities\LogEntry;

/**
 * Stores record as a DB entry
 * 
 * Some of these may be expected to expire
 */
class TableLogHandler implements NfLogHandler
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

        $this->maybeAddTable();

        $this->storeLogEntry($logEntry);
    }

    /** @inheritDoc */
    public function getPluginLogEntries( ): array
    {
        return $this->getLogEntriesByParam('plugin', $this->pluginPrefix);
    }

    /**
     * Get every log entry in the table
     * 
     * @return array
     */
    public function getAllLogEntries(): array
    {
        return $this->getLogEntriesByParam('all', 'all');
    }

    /**
     * Get log entries by log level
     * 
     * @param string $requested The requested level to fetch
     * @return array
     */
    public function getLogEntriesByLevel( $requested ): array
    {
        $levels = array(
            'emergency',
            'alert',
            'critical',
            'error',
            'warning',
            'notice',
            'info',
            'debug',
            'log'
        );
        if( ! \in_array( $requested, $levels ) ) $requested = 'log';
        return $this->getLogEntriesByParam('level', $requested);
    }

    /**
     * Get log entries by caller
     * 
     * @param string $identifier The target log point
     * @return array
     */
    public function getCallerLogEntries( $identifier ): array
    {
        return $this->getLogEntriesByParam('caller', $identifier);
    }

    /**
     * Base method for getting log entries from the table
     * 
     * @param string $key the column to narrow our results by
     * @param mixed $value the value(s) to apply in our search
     * @return array
     */
    protected function getLogEntriesByParam( $key, $value )
    {
        if( ! $this->tableExists() ) return [];
        global $wpdb;
        $sql = "SELECT * FROM `" . $wpdb->prefix . "nf3_log`";
        switch($key) {
            case 'level':
            case 'plugin':
            case 'caller':
                $sql .= " WHERE {$key} = '{$value}'";
                break;
            default:
                break;
        }
        
        $resultArray = $wpdb->get_results($sql, 'ARRAY_A');

        $logArrayCollection = \array_map(
            function($logEntryArray){
                return LogEntry::fromString((string) $logEntryArray['content']);
            },
            $resultArray
        );

        return $logArrayCollection;
    }

    /** @inheritDoc */
    public function deletePluginLogEntries(): void
    {
        global $wpdb;
        $wpdb->delete(
            $wpdb->prefix . 'nf3_log',
            ['plugin' => $this->pluginPrefix]
        );
    }

    /**
     * Construct log entry object from log request
     *
     * @param   $message
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
     * Add our table if necessary
     * 
     * @return void
     */
    protected function maybeAddTable(): void
    {
        if( $this->tableExists() ) return;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        global $wpdb;
        
        $sql = "CREATE TABLE `" . $wpdb->prefix . "nf3_log` (
            `id` int NOT NULL AUTO_INCREMENT,
            `level` varchar(10) DEFAULT 'log',
            `caller` longtext,
            `plugin` varchar(50),
            `content` longtext,
            UNIQUE KEY (`id`)
            )";
        
            \dbDelta( $sql );
    }

    /**
     * Verify our table exists
     * 
     * @return boolean
     */
    protected function tableExists(): bool
    {
        global $wpdb;

        $sql = "SHOW TABLES FROM `$wpdb->dbname` WHERE `Tables_in_$wpdb->dbname` LIKE '" . $wpdb->prefix . "nf3_log'";

        $result = $wpdb->get_results( $sql, 'ARRAY_A' );

        if( empty( $result ) ) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Store entry in our table
     *
     * @param LogEntry $logEntry
     * @return void
     */
    protected function storeLogEntry(LogEntry $logEntry): void
    {
        $data = [
            'content' => $logEntry->__toString(),
            'level'=>$logEntry->getLevel(),
            'caller'=>$logEntry->getLogPoint(),
            'plugin'=>$this->pluginPrefix
        ];

        global $wpdb;
        $wpdb->insert($wpdb->prefix . 'nf3_log', $data);

    }

    /** @inheritDoc */
    public function setPluginPrefix(string $prefix): NfLogHandler{
        $this->pluginPrefix = $prefix;
        return $this;
    }
}
