<?php

namespace NinjaForms\FileUploads\Common\Interfaces;

use NinjaForms\FileUploads\Common\Interfaces\NfLogHandler;
use NinjaForms\FileUploads\Common\VendorDist\Psr\Log\LoggerInterface;


interface NfLogger extends LoggerInterface
{

    /**
     * Push a log handler onto the stack of handlers
     *
     * @param LogHandler $handler
     * @param string $logLevel
     * @return void
     */
    public function pushLogHandler(NfLogHandler $handler, string $logLevel): void;
}
