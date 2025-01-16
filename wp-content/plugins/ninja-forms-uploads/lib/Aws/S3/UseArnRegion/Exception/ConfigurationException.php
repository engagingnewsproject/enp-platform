<?php

namespace NF_FU_LIB\Aws\S3\UseArnRegion\Exception;

use NF_FU_LIB\Aws\HasMonitoringEventsTrait;
use NF_FU_LIB\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration for S3's UseArnRegion
 */
class ConfigurationException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
