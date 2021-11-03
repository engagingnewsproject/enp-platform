<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_Abstracts_Routes
 */
abstract class NF_Abstracts_Routes
{
    /**
    * Register the API routes
    *
    *  @since 3.4.33
    */
    public function __construct(){
        add_action('rest_api_init', [ $this, 'register_routes'] );
    }

    /**
     * Register Routes
     */
    public abstract function register_routes();

} 
