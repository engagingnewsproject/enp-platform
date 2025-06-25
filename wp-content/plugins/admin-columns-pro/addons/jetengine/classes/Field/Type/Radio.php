<?php

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\GlossaryOptions;
use ACA\JetEngine\Field\GlossaryOptionsTrait;
use ACA\JetEngine\Field\ManualBulkOptions;
use ACA\JetEngine\Field\ManualBulkOptionsTrait;
use ACA\JetEngine\Field\Options;
use ACA\JetEngine\Field\OptionsTrait;

class Radio extends Field implements Options, GlossaryOptions, ManualBulkOptions
{

    use GlossaryOptionsTrait;
    use OptionsTrait;
    use ManualBulkOptionsTrait;

    const TYPE = 'radio';

}