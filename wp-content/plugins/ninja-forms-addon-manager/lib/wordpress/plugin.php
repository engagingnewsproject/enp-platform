<?php

namespace NinjaFormsAddonManager\WordPress;

abstract class Plugin
{
    protected static $instance;

    protected $version;
    protected $url;
    protected $dir;

    public static function getInstance() {
        if ( null == self::$instance ) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
    private function __wakeup() {}

    /*
    |--------------------------------------------------------------------------
    | Plugin Getters
    |--------------------------------------------------------------------------
    */

    public static function url( $url = '' ) {
        return trailingslashit( self::$instance->url ) . $url;
    }

    public static function dir( $path = '' ) {
        return trailingslashit( self::$instance->dir ) . $path;
    }

    public static function config( $file_name, $key = false ) {
        $config = include self::dir( 'includes/config/' . $file_name . '.php' );
        return ( $key ) ? $config[ $key ] : $config;
    }

    public static function view( $file, $args = array() ) {
        $path = self::dir( 'includes/resources/views/' . $file );
        if( ! file_exists( $path ) ) return '';
        $plugin = '\\' . get_called_class();
        extract( $args );
        ob_start();
        include $path;
        return ob_get_clean();
    }
}
