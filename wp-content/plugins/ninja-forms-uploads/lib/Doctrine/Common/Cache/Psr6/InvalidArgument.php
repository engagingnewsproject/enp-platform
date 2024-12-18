<?php

namespace NF_FU_LIB\Doctrine\Common\Cache\Psr6;

use InvalidArgumentException;
use NF_FU_LIB\Psr\Cache\InvalidArgumentException as PsrInvalidArgumentException;

/**
 * @internal
 */
final class InvalidArgument extends InvalidArgumentException implements PsrInvalidArgumentException
{
}
