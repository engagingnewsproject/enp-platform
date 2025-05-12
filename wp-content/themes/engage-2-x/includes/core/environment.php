<?php
/**
 * Environment configuration
 * 
 * This file contains configuration for the environment, including

**/

// Define environment constants
$site_url = get_home_url();
if (strpos($site_url, '.com') === false) {
    // Local development
    define('ENV_PRODUCTION', false);
} elseif (strpos($site_url, 'cmedev.wpengine.com') !== false) {
    // Development environment
    define('ENV_PRODUCTION', false);
} else {
    // Production environment
    define('ENV_PRODUCTION', true);
}

// For backward compatibility
$engageEnv = ENV_PRODUCTION ? 'PROD' : 'DEV';
define('ENGAGE_ENV', $engageEnv);

// Add environment variables to Timber context
add_filter('timber/context', function($context) {
    $context['ENGAGE_ENV'] = ENGAGE_ENV;
    $context['ENV_PRODUCTION'] = ENV_PRODUCTION;
    return $context;
});

// Cache twig in production only
$cache_time = ENV_PRODUCTION ? [
    MINUTE_IN_SECONDS * 5, // logged out, 5 min cache
    false // if logged in, no cache
] : false;
define('ENGAGE_PAGE_CACHE_TIME', $cache_time); 