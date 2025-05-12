<?php
/**
 * Asset-related hooks and filters
 * 
 * This file contains all hooks and filters related to asset loading and optimization,
 * including script and style modifications.
 */

/**
 * Defer or async scripts from plugins
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    // List of script handles to defer
    $defer_scripts = [
        'file_uploads_nfpluginsettings',
        'nf-front-end-deps',
        'nf-front-end',
    ];

    // List of script handles to async
    $async_scripts = [
        'plugin-script-handle-3',
    ];

    if (in_array($handle, $defer_scripts)) {
        return str_replace('<script ', '<script defer ', $tag);
    }

    if (in_array($handle, $async_scripts)) {
        return str_replace('<script ', '<script async ', $tag);
    }

    return $tag;
}, 10, 3);

/**
 * Defer stylesheets from plugins
 */
add_filter('style_loader_tag', function ($tag, $handle, $href) {
    // List of stylesheet handles to defer
    $defer_styles = [
        'nf-display',
        'wp-block-library',
    ];

    if (in_array($handle, $defer_styles)) {
        return str_replace(
            '<link ',
            '<link media="print" onload="this.media=\'all\'" ',
            $tag
        );
    }
    return $tag;
}, 10, 3); 