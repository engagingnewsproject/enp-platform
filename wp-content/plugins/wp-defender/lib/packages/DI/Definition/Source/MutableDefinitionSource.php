<?php

declare(strict_types=1);

namespace WPMU_DEV\Defender\Vendor\DI\Definition\Source;

use WPMU_DEV\Defender\Vendor\DI\Definition\Definition;

/**
 * Describes a definition source to which we can add new definitions.
 *
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
interface MutableDefinitionSource extends DefinitionSource
{
    public function addDefinition(Definition $definition);
}