<?php

namespace wpengine\cache_plugin;

abstract class PluginRestPaths {

	const BASE                   = 'wpe/cache-plugin/v1';
	const CLEAR_ALL_CACHES_PATH  = '/clear_all_caches';
	const RATE_LIMIT_STATUS_PATH = '/rate_limit_status';
}
