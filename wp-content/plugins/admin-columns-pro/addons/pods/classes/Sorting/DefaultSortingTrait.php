<?php

declare(strict_types=1);

namespace ACA\Pods\Sorting;

use AC\Setting\Config;
use ACP;

trait DefaultSortingTrait
{

    public function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create($this->field->get_meta_type(), $this->field->get_name());
    }

}