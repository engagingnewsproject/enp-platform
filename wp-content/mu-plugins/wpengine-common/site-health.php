<?php
/** Functions relating to site health data storage for use in the go live checklist.
 *
 * @package wpengine/common-mu-plugin
 */

/**
 * A filter that stores the site health check results in the database for access by the go live checklist.
 * It is necessary to store it in this fashion as it is set as a transient by default, which is not guaranteed
 * to be saved in the database.
 *
 * @param string $value the value of the transient being saved.
 * @return string the unchanged value of the transient.
 */
function store_site_health_results_in_db( $value ) {
	update_option( 'wpe-health-check-site-status-result', $value );

	return $value;
}

add_filter( 'pre_set_transient_health-check-site-status-result', 'store_site_health_results_in_db' );
