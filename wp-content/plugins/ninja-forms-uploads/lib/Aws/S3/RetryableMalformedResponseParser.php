<?php

namespace NF_FU_LIB\Aws\S3;

use NF_FU_LIB\Aws\Api\Parser\AbstractParser;
use NF_FU_LIB\Aws\Api\StructureShape;
use NF_FU_LIB\Aws\Api\Parser\Exception\ParserException;
use NF_FU_LIB\Aws\CommandInterface;
use NF_FU_LIB\Aws\Exception\AwsException;
use NF_FU_LIB\Psr\Http\Message\ResponseInterface;
use NF_FU_LIB\Psr\Http\Message\StreamInterface;
/**
 * Converts malformed responses to a retryable error type.
 *
 * @internal
 */
class RetryableMalformedResponseParser extends AbstractParser
{
    /** @var string */
    private $exceptionClass;
    public function __construct(callable $parser, $exceptionClass = AwsException::class)
    {
        $this->parser = $parser;
        $this->exceptionClass = $exceptionClass;
    }
    public function __invoke(CommandInterface $command, ResponseInterface $response)
    {
        $fn = $this->parser;
        try {
            return $fn($command, $response);
        } catch (ParserException $e) {
            throw new $this->exceptionClass("Error parsing response for {$command->getName()}:" . " AWS parsing error: {$e->getMessage()}", $command, ['connection_error' => \true, 'exception' => $e], $e);
        }
    }
    public function parseMemberFromStream(StreamInterface $stream, StructureShape $member, $response)
    {
        return $this->parser->parseMemberFromStream($stream, $member, $response);
    }
}
