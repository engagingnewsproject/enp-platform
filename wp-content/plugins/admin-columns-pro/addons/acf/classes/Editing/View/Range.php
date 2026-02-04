<?php

declare(strict_types=1);

namespace ACA\ACF\Editing\View;

use ACA;
use ACP\Editing\View;

class Range extends View
{

    use View\MinMaxTrait;
    use View\StepTrait;

    public function __construct()
    {
        parent::__construct('acf_range');
    }

    public function set_default_value(string $default_value)
    {
        $this->set('default_value', $default_value);
    }

}