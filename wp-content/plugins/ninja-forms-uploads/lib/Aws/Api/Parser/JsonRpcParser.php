<?php

namespace NF_FU_LIB\Aws\Api\Parser;

use NF_FU_LIB\Aws\Api\StructureShape;
use NF_FU_LIB\Aws\Api\Service;
use NF_FU_LIB\Aws\Result;
use NF_FU_LIB\Aws\CommandInterface;
use NF_FU_LIB\Psr\Http\Message\ResponseInterface;
use NF_FU_LIB\Psr\Http\Message\StreamInterface;
/**
 * @internal Implements JSON-RPC parsing (e.g., DynamoDB)
 */
class JsonRpcParser extends AbstractParser
{
    use PayloadParserTrait;
    /**
     * @param Service    $api    Service description
     * @param JsonParser $parser JSON body builder
     */
    public function __construct(Service $api, JsonParser $parser = null)
    {
        parent::__construct($api);
        $this->parser = $parser ?: new JsonParser();
    }
    public function __invoke(CommandInterface $command, ResponseInterface $response)
    {
        $operation = $this->api->getOperation($command->getName());
        $result = null === $operation['output'] ? null : $this->parseMemberFromStream($response->getBody(), $operation->getOutput(), $response);
        return new Result($result ?: []);
    }
    public function parseMemberFromStream(StreamInterface $stream, StructureShape $member, $response)
    {
        return $this->parser->parse($member, $this->parseJson($stream, $response));
    }
}
