<?php

namespace NF_FU_LIB\Aws\ClientSideMonitoring;

use NF_FU_LIB\Aws\CommandInterface;
use NF_FU_LIB\Aws\Exception\AwsException;
use NF_FU_LIB\Aws\ResultInterface;
use NF_FU_LIB\GuzzleHttp\Psr7\Request;
use NF_FU_LIB\Psr\Http\Message\RequestInterface;
/**
 * @internal
 */
interface MonitoringMiddlewareInterface
{
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param RequestInterface $request
     * @return array
     */
    public static function getRequestData(RequestInterface $request);
    /**
     * Data for event properties to be sent to the monitoring agent.
     *
     * @param ResultInterface|AwsException|\Exception $klass
     * @return array
     */
    public static function getResponseData($klass);
    public function __invoke(CommandInterface $cmd, RequestInterface $request);
}
