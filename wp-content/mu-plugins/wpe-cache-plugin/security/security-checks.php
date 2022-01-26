<?php

namespace wpengine\cache_plugin;

\wpengine\cache_plugin\check_security();

function check_security() {
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}
}
