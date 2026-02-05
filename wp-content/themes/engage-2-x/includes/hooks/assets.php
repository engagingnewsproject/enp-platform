<?php
/**
 * Asset-related hooks and filters
 * 
 * This file contains all hooks and filters related to asset loading and optimization,
 * including script and style modifications.
 */

/**
 * Defer scripts only on the frontend to reduce render-blocking.
 * - wp-admin: never defer (strip defer/async from all admin scripts) so plugin load order is preserved.
 * - Frontend: defer only our theme bundles. Do not defer jQuery, Backbone, or plugin scripts (Ninja Forms,
 *   Download Monitor, etc.) — deferring them causes "jQuery is not defined" and breaks forms because
 *   non-deferred scripts run before deferred ones.
 */
add_filter('script_loader_tag', function ($tag, $handle, $src) {
    if (is_admin()) {
        // No defer/async in admin — preserve strict script order for plugins (Ninja Forms, etc.).
        while (preg_match('/\s+(defer|async)(?=\s|>)/i', $tag)) {
            $tag = preg_replace('/\s+(defer|async)(?=\s|>)/i', '', $tag);
        }
        return $tag;
    }

    // Defer only our theme scripts. Leave jQuery, plugins (Ninja Forms, Download Monitor), and their
    // deps (backbone, underscore, nf-front-end*) loading synchronously so dependency order is preserved.
    $defer_scripts = [
        'engage/js',
        'homepage/js',
    ];

    $async_scripts = [
        'plugin-script-handle-3',
    ];

    if (in_array($handle, $defer_scripts, true) && strpos($tag, ' defer') === false && strpos($tag, ' async') === false) {
        return str_replace('<script ', '<script defer ', $tag);
    }

    if (in_array($handle, $async_scripts)) {
        return str_replace('<script ', '<script async ', $tag);
    }

    return $tag;
}, 999, 3);

/**
 * Load non-critical styles asynchronously (media="print" + onload) to reduce render-blocking.
 * Applied only on the frontend.
 */
add_filter('style_loader_tag', function ($tag, $handle, $href) {
    if (is_admin()) {
        return $tag;
    }

    $async_styles = [
        'engage_css',       // Theme main CSS
        'nf-display',
        'wp-block-library',
    ];

    if (in_array($handle, $async_styles)) {
        return str_replace(
            '<link ',
            '<link media="print" onload="this.media=\'all\'" ',
            $tag
        );
    }
    return $tag;
}, 10, 3); 