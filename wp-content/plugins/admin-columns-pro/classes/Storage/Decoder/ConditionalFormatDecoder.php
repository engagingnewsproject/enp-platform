<?php

declare(strict_types=1);

namespace ACP\Storage\Decoder;

use ACP\ConditionalFormat\RulesCollection;

interface ConditionalFormatDecoder
{

    public function has_conditional_formatting(): bool;

    public function get_conditional_formatting(): RulesCollection;

}