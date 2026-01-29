<?php

declare(strict_types=1);

namespace ACA\WC\Editing\View;

use ACP;

class Price extends ACP\Editing\View
{

    public function __construct(string $default_type)
    {
        parent::__construct('wc_price_extended');

        $this->set_default_type($default_type);
        $this->set_revisioning(false);
    }

    public function set_default_type(string $default_type): Price
    {
        return $this->set('default_type', $default_type);
    }

}
