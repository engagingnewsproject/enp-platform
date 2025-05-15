<?php

namespace ACP\Sorting;

/**
 * @deprecated 6.4
 */
interface ListScreen
{

    public function sorting(AbstractModel $model): Strategy;

}