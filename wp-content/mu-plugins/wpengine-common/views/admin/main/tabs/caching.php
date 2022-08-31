<?php
/**
 * Admin UI - Caching Tab
 * Adds the WP Engine Admin "Caching" tab.
 *
 * @package wpengine/common-mu-plugin
 */

// Don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

// Check user capabilities.
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

?>

<!-- Caching Tab -->
<div class="wpe-common-plugin-container">
	<h2>Quick Actions</h2>
	<p>Caching reduces the load on your site by storing the results of a request, so that it can be served to the next visitor. This significantly increases the speed of the site. However, this can lead to a visitor receiving an old version of the page. This can be fixed by clearing the cache below.</p>
	<p><strong>Note:</strong> Cache is cleared by environment, not by domain. This does not clear <strong>Global Edge Security</strong> cache.</p>
	<div class="wpe-admin-button-controls">
		<a href="#" target="_blank" rel="noopener noreferrer">Clear GES caches by domain in Portal</a>
		<a href="#" target="_blank" rel="noopener noreferrer">Learn more about caching</a>
	</div>
	<div class="wpe-common-cta-panel">
		<div class="wpe-common-cta-wrap">
		<span class="wpe-admin-icon-check-solid"></span>
			<div class="wpe-common-cta-text">
				<p class="wpe-common-cta-heading"><strong>Clear all caches at once</strong></p>
				<p>This will slow down your site until caches are rebuilt.</p>
			</div>
		</div>
		<div class="wpe-common-cta-button">
			<button class="wpe-admin-button-primary">Clear all caches</button>
		</div>
	</div>
	<div class="wpe-common-cta-panel wpe-completed">
		<div class="wpe-common-cta-wrap">
		<span class="wpe-admin-icon-check-solid"></span>
			<div class="wpe-common-cta-text">
				<p class="wpe-common-cta-heading"><strong>Clear all caches at once</strong></p>
				<p>This will slow down your site until caches are rebuilt.</p>
			</div>
		</div>
		<div class="wpe-common-cta-button">
			<button disabled class="wpe-admin-button-primary">Clear all caches</button>
		</div>
	</div>
</div>
