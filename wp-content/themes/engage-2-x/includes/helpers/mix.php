<?php
/**
 * Mix Helper
 * 
 * This file contains the mix helper function

**/
if (!function_exists('mix')) {
    function mix($path) {
        $manifestPath = get_stylesheet_directory() . '/dist/mix-manifest.json';
        static $manifest = null;

        if ($manifest === null && file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
        }

        $path = '/' . ltrim($path, '/');
        if ($manifest && isset($manifest[$path])) {
            return get_stylesheet_directory_uri() . '/dist' . $manifest[$path];
        }

        // fallback to non-versioned file
        return get_stylesheet_directory_uri() . '/dist' . $path;
    }
}