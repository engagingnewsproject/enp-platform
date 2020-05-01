<?php

namespace wpengine\sign_on_plugin;

\wpengine\sign_on_plugin\check_security();

function check_security() {
	if ( ! defined( 'ABSPATH' ) ) {
		exit; // Exit if accessed directly.
	}
}
