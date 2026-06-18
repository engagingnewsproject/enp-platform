<?php

declare(strict_types=1);

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\GlossaryOptions;
use ACA\JetEngine\Field\GlossaryOptionsTrait;
use ACA\JetEngine\Field\ManualBulkOptions;
use ACA\JetEngine\Field\ManualBulkOptionsTrait;
use ACA\JetEngine\Field\Multiple;
use ACA\JetEngine\Field\MultipleTrait;
use ACA\JetEngine\Field\Options;
use ACA\JetEngine\Field\OptionsTrait;

final class Select extends Field implements Options, GlossaryOptions, Multiple, ManualBulkOptions
{

    public const TYPE = 'select';

    use GlossaryOptionsTrait;
    use MultipleTrait;
    use OptionsTrait;
    use ManualBulkOptionsTrait;
}