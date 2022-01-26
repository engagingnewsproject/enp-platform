<?php
declare(strict_types=1);

namespace wpengine\cache_plugin;

\wpengine\cache_plugin\check_security();

abstract class CacheSettingValues {
	const FOUR_WEEKS_IN_SECONDS   = 60 * 60 * 24 * 7 * 4;
	const SIX_MONTHS_IN_SECONDS   = 60 * 60 * 24 * 30 * 6;
	const SETTING_DEFAULT_VALUE   = -1;
	const TWELVE_HOURS_IN_SECONDS = 43200;
	const FORTY_YEARS_IN_SECONDS  = 1262304000;
}
