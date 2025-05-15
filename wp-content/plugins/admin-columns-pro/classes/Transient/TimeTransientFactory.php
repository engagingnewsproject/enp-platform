<?php

declare(strict_types=1);

namespace ACP\Transient;

class TimeTransientFactory
{

    public static function create_update_check(): TimeTransient
    {
        return new TimeTransient('acp_periodic_update_plugins_check', HOUR_IN_SECONDS * 12);
    }

    public static function create_update_check_hourly(): TimeTransient
    {
        return new TimeTransient('acp_periodic_update_plugins_check_hourly', HOUR_IN_SECONDS);
    }

    public static function create_license_check_daily(): TimeTransient
    {
        return new TimeTransient('acp_periodic_license_check', DAY_IN_SECONDS);
    }

}