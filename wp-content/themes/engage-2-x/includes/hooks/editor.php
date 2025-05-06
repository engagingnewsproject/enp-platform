<?php
/**
 * Editor-related hooks and filters
 * 
 * This file contains all hooks and filters related to the WordPress editor,
 * including block editor and classic editor customizations.
 */

// Add theme support for editor features
add_theme_support('align-wide');
add_post_type_support('page', 'excerpt');

/**
 * Debug function to dump Twig functions that Timber provides
 * Uncomment to use
 */
/*
add_filter('timber/twig/functions', function ($functions) {
    var_dump($functions);
    return $functions;
});
*/ 