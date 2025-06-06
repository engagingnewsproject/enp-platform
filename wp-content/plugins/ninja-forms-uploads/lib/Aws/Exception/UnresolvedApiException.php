<?php

namespace NF_FU_LIB\Aws\Exception;

use NF_FU_LIB\Aws\HasMonitoringEventsTrait;
use NF_FU_LIB\Aws\MonitoringEventsInterface;
class UnresolvedApiException extends \RuntimeException implements MonitoringEventsInterface
{
    use HasMonitoringEventsTrait;
}
