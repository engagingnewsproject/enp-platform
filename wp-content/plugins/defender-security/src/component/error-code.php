<?php
/**
 * Author: Hoang Ngo
 */

namespace WP_Defender\Component;

class Error_Code {
	public const NOT_WRITEABLE     = 1;
	public const WPDEBUG_NOT_FOUND = 2;
	public const UNKNOWN_WPCONFIG  = 3;
	public const IS_EMPTY          = 4;
	public const VALIDATE          = 5;
	public const SQL_ERROR         = 6;
	public const DB_ERROR          = 7;
	public const INVALID           = 8;
	public const SCAN_ERROR        = 9;
	public const API_ERROR         = 10;
}
