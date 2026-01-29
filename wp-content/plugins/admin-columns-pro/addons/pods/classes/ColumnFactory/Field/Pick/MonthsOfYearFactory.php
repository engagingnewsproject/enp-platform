<?php

declare(strict_types=1);

namespace ACA\Pods\ColumnFactory\Field\Pick;

use AC\Setting\Config;
use ACP;
use PodsField_Pick;

class MonthsOfYearFactory extends BasePickFactory
{

    protected function get_options(): array
    {
        return (new PodsField_Pick())->data_months_of_year();
    }

    protected function get_sorting(Config $config): ?ACP\Sorting\Model\QueryBindings
    {
        return (new ACP\Sorting\Model\MetaFactory())->create($this->field->get_meta_type(), $this->field->get_name());
    }

}