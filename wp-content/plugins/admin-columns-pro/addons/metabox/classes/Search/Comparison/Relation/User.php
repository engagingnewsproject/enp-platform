<?php

declare(strict_types=1);

namespace ACA\MetaBox\Search\Comparison\Relation;

use ACA\MetaBox\Search;
use ACP;

class User extends Search\Comparison\Relation
{

    use ACP\Search\UserValuesTrait;
}