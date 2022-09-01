<?php
declare(strict_types=1);

namespace wpengine\cache_plugin;

require_once __DIR__ . '/security/security-checks.php';

\wpengine\cache_plugin\check_security();

class MaxCDNProvider {
	public function is_enabled() {
		return $this->has_netdna_domains() || $this->has_secure_netdna_domains();
	}

	public function has_netdna_domains() {
		global $wpe_netdna_domains;

		if ( is_null( $wpe_netdna_domains ) || ! is_array( $wpe_netdna_domains ) ) {
			return false;
		}

		return count( $wpe_netdna_domains ) > 0;
	}

	public function has_secure_netdna_domains() {
		global $wpe_netdna_domains_secure;

		if ( is_null( $wpe_netdna_domains_secure ) || ! is_array( $wpe_netdna_domains_secure ) ) {
			return false;
		}

		return count( $wpe_netdna_domains_secure ) > 0;
	}
}
