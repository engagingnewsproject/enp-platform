<?php

namespace ACA\JetEngine\Field\Type;

use ACA\JetEngine\Field\Field;
use ACA\JetEngine\Field\GlossaryOptions;
use ACA\JetEngine\Field\GlossaryOptionsTrait;
use ACA\JetEngine\Field\ManualBulkOptions;
use ACA\JetEngine\Field\ManualBulkOptionsTrait;
use ACA\JetEngine\Field\Options;
use ACA\JetEngine\Field\OptionsTrait;

class Checkbox extends Field implements Options, GlossaryOptions, ManualBulkOptions
{

    use GlossaryOptionsTrait;
    use OptionsTrait;
    use ManualBulkOptionsTrait;

    const TYPE = 'checkbox';

    public function value_is_array()
    {
        return isset($this->settings['is_array']) && $this->settings['is_array'];
    }

}