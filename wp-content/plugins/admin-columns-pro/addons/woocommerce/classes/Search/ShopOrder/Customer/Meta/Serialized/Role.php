<?php

declare(strict_types=1);

namespace ACA\WC\Search\ShopOrder\Customer\Meta\Serialized;

use AC;
use AC\Helper\Select\Options;
use ACA\WC\Search\ShopOrder\Customer\Meta\Serialized;

class Role extends Serialized
{

    private array $roles;

    public function __construct(array $roles)
    {
        $this->roles = $roles;

        parent::__construct('wp_capabilities');
    }

    public function get_values(): Options
    {
        return AC\Helper\Select\Options::create_from_array($this->roles);
    }

}