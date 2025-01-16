<?php

namespace NF_FU_LIB\Aws\Handler\GuzzleV5;

use NF_FU_LIB\GuzzleHttp\Stream\StreamDecoratorTrait;
use NF_FU_LIB\GuzzleHttp\Stream\StreamInterface as GuzzleStreamInterface;
use NF_FU_LIB\Psr\Http\Message\StreamInterface as Psr7StreamInterface;
/**
 * Adapts a PSR-7 Stream to a Guzzle 5 Stream.
 *
 * @codeCoverageIgnore
 */
class GuzzleStream implements GuzzleStreamInterface
{
    use StreamDecoratorTrait;
    /** @var Psr7StreamInterface */
    private $stream;
    public function __construct(Psr7StreamInterface $stream)
    {
        $this->stream = $stream;
    }
}
