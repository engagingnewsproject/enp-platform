<?php

declare(strict_types=1);

namespace WPMU_DEV\Defender\Vendor\DI\Definition\Source;

use WPMU_DEV\Defender\Vendor\DI\Definition\Definition;
use WPMU_DEV\Defender\Vendor\DI\Definition\Exception\InvalidDefinition;

/**
 * Source of definitions for entries of the container.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface DefinitionSource
{
    /**
     * Returns the WPMU_DEV\Defender\Vendor\DI definition for the entry name.
     *
     * @throws InvalidDefinition An invalid definition was found.
     * @return Definition|null
     */
    public function getDefinition(string $name);

    /**
     * @return Definition[] Definitions indexed by their name.
     */
    public function getDefinitions() : array;
}