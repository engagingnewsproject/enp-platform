<?php
/**
 * Constants of WPMUDEV class.
 *
 * @package WP_Defender\Behavior
 */

namespace WP_Defender\Behavior;

interface WPMUDEV_Const_Interface {
	const API_SCAN_SIGNATURE  = 'yara_scan', API_SCAN_KNOWN_VULN = 'known_vulnerability';
	const API_AUDIT           = 'audit_logging', API_AUDIT_ADD = 'audit_logging_add';
	const API_BLACKLIST       = 'blacklist', API_WAF = 'waf', API_HUB_SYNC = 'hub_sync';
	const API_PACKAGE_CONFIGS = 'package_configs';
}
