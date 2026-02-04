<?php

declare(strict_types=1);

namespace ACA\BP\Value\Formatter\User;

use AC\Formatter;
use AC\Type\Value;

class LastActivity implements Formatter
{

    private array $actions;

    public function __construct()
    {
        $this->actions = bp_activity_admin_get_activity_actions();
    }

    public function format(Value $value): Value
    {
        $activity = $this->get_last_activity($value->get_id());

        if ( ! $activity) {
            return new Value(null);
        }

        return $value->with_value(
            $this->actions[$activity->type] ?? sprintf(__('Unregistered action - %s', 'buddypress'), $activity->type)
        );
    }

    private function get_last_activity($user_id)
    {
        $activities = bp_activity_get([
            'max'              => 1,
            'per_page'         => 1,
            'display_comments' => 'stream',
            'filter'           => [
                'user_id' => $user_id,
            ],
            'show_hidden'      => true,
        ]);

        return ! empty($activities['activities'])
            ? $activities['activities'][0]
            : null;
    }
}