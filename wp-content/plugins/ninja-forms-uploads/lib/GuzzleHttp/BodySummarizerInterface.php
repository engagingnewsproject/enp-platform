<?php

namespace NF_FU_LIB\GuzzleHttp;

use NF_FU_LIB\Psr\Http\Message\MessageInterface;

interface BodySummarizerInterface
{
    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string;
}
