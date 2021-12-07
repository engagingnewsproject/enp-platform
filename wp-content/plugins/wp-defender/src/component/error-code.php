<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component;

class Error_Code {
	const NOT_WRITEABLE     = 1;
	const WPDEBUG_NOT_FOUND = 2;
	const UNKNOWN_WPCONFIG  = 3;
	const IS_EMPTY          = 4;
	const VALIDATE          = 5;
	const SQL_ERROR         = 6;
	const DB_ERROR          = 7;
	const INVALID           = 8;
	const SCAN_ERROR        = 9;
	const API_ERROR         = 10;
}