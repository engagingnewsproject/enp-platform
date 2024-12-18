<?php

namespace NF_FU_LIB\Aws\Api\Parser;

use NF_FU_LIB\Aws\Api\Service;
use NF_FU_LIB\Aws\Api\StructureShape;
use NF_FU_LIB\Aws\CommandInterface;
use NF_FU_LIB\Aws\ResultInterface;
use NF_FU_LIB\Psr\Http\Message\ResponseInterface;
use NF_FU_LIB\Psr\Http\Message\StreamInterface;
/**
 * @internal
 */
abstract class AbstractParser
{
    /** @var \Aws\Api\Service Representation of the service API*/
    protected $api;
    /** @var callable */
    protected $parser;
    /**
     * @param Service $api Service description.
     */
    public function __construct(Service $api)
    {
        $this->api = $api;
    }
    /**
     * @param CommandInterface  $command  Command that was executed.
     * @param ResponseInterface $response Response that was received.
     *
     * @return ResultInterface
     */
    public abstract function __invoke(CommandInterface $command, ResponseInterface $response);
    public abstract function parseMemberFromStream(StreamInterface $stream, StructureShape $member, $response);
}
