<?php

namespace ACP\Formatter\NetworkSite;

use AC\Formatter;
use AC\Helper;
use AC\Type\Value;

class Status implements Formatter
{

    public function format(Value $value): Value
    {
        $values = [];

        $site = get_site($value->get_id());

        if ( ! $site) {
            return $value->with_value('');
        }

        foreach ($this->get_statuses() as $status => $label) {
            if ( ! empty($site->{$status})) {
                $values[] = $label;
            }
        }

        return $value->with_value(Helper\Html::create()->implode($values));
    }

    private function get_statuses(): array
    {
        return [
            'public'   => __('Public'),
            'archived' => __('Archived'),
            'spam'     => _x('Spam', 'site'),
            'deleted'  => __('Deleted'),
            'mature'   => __('Mature'),
        ];
    }
}