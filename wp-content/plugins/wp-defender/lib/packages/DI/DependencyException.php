<?php

declare(strict_types=1);

namespace WPMU_DEV\Defender\Vendor\DI;

use WPMU_DEV\Defender\Vendor\Psr\Container\ContainerExceptionInterface;

/**
 * Exception for the Container.
 */
class DependencyException extends \Exception implements ContainerExceptionInterface
{
}