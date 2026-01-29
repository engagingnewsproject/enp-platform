<?php

declare(strict_types=1);

namespace ACP\Export\Formatter;

use AC\Formatter;
use AC\Type\Value;
use ACP;

class EscapeData implements Formatter
{

    private ACP\Export\EscapeData $escaper;

    public function __construct(ACP\Export\EscapeData $escaper)
    {
        $this->escaper = $escaper;
    }

    public function format(Value $value)
    {
        return $value->with_value(
            $this->escaper->escape((string)$value)
        );
    }

}