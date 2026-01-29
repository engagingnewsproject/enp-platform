<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\Group;

use AC\Formatter;
use AC\Type\Value;

class GroupLink implements Formatter
{

    private string $link_to;

    public function __construct(string $link_to)
    {
        $this->link_to = $link_to;
    }

    public function format(Value $value): Value
    {
        switch ($this->link_to) {
            case 'edit_group':
                $link = bp_get_admin_url('admin.php?action=edit&page=bp-groups&gid=' . $value->get_id());
                break;
            case 'view_group' :
                $link = bp_get_group_url($value->get_id());

                break;
            default :
                $link = false;
        }

        return $link
            ? $value->with_value(ac_helper()->html->link($link, $value->get_value()))
            : $value;
    }

}