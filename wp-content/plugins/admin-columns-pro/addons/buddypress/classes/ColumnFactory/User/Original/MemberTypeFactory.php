<?php

declare(strict_types=1);

namespace ACA\BP\ColumnFactory\User\Original;

use AC\Setting\Config;
use ACA\BP;
use ACP\Column\OriginalColumnFactory;
use ACP\Editing;
use ACP\Search;

class MemberTypeFactory extends OriginalColumnFactory
{

    private function get_member_types(): array
    {
        $options = [];

        foreach (bp_get_member_types([], 'objects') as $key => $type) {
            $options[$key] = $type->labels['singular_name'] ?? $type->name;
        }

        return $options;
    }

    protected function get_editing(Config $config): ?Editing\Service
    {
        return new BP\Editing\Service\User\Membertype($this->get_member_types());
    }

    protected function get_search(Config $config): ?Search\Comparison
    {
        return new BP\Search\User\MemberTypes($this->get_member_types());
    }

}