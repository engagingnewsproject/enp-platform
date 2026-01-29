<?php

declare(strict_types=1);

namespace ACA\ACF\Value\Formatter;

use AC\Exception\ValueNotFoundException;
use AC\Formatter;
use AC\Type\TableScreenContext;
use AC\Type\Value;
use ACA\ACF\Utils\AcfId;

class GroupField implements Formatter
{

    private string $group_key;

    private TableScreenContext $table_context;

    private string $sub_key;

    public function __construct(TableScreenContext $table_context, string $group_key, string $sub_key)
    {
        $this->group_key = $group_key;
        $this->table_context = $table_context;
        $this->sub_key = $sub_key;
    }

    public function format(Value $value)
    {
        $raw = get_field($this->group_key, AcfId::get_id($value->get_id(), $this->table_context), false);

        if ($raw === null) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        if ( ! isset($raw[$this->sub_key])) {
            throw ValueNotFoundException::from_id($value->get_id());
        }

        return $value->with_value($raw[$this->sub_key]);
    }

}