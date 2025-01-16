<?php

namespace NF_FU_LIB\Aws\ClientSideMonitoring\Exception;

use NF_FU_LIB\Aws\HasMonitoringEventsTrait;
use NF_FU_LIB\Aws\MonitoringEventsInterface;
/**
 * Represents an error interacting with configuration for client-side monitoring.
 */
class ConfigurationException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
