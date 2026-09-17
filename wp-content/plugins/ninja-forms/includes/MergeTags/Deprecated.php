<?php if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class NF_MergeTags_WordPress
 */
final class NF_MergeTags_Deprecated extends NF_Abstracts_MergeTags
{
    protected $id = 'deprecated';

    public function __construct()
    {
        parent::__construct();
        $this->merge_tags = Ninja_Forms()->config( 'MergeTagsDeprecated' );
    }

    protected function post_id()
    {
        global $post;

        if ( is_admin() && defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            // If we are doing AJAX, use the referer to get the Post ID.
            $post_id = url_to_postid( wp_get_referer() );
        } elseif( $post ) {
            $post_id = $post->ID;
        } else {
            return false; // No Post ID found.
        }

        return $post_id;
    }

    protected function post_title()
    {
        $post_id = $this->post_id();
        if( ! $post_id ) return;
        $post = get_post( $post_id );
        return ( $post ) ? $post->post_title : '';
    }

    protected function post_url()
    {
        $post_id = $this->post_id();
        if( ! $post_id ) return;
        $post = get_post( $post_id );
        return ( $post ) ? get_permalink( $post->ID ) : '';
    }

    protected function post_author()
    {
        $post_id = $this->post_id();
        if( ! $post_id ) return;
        $post = get_post( $post_id );
        if( ! $post ) return '';
        $author = get_user_by( 'id', $post->post_author);
        return $author->display_name;
    }

    protected function post_author_email()
    {
        $post_id = $this->post_id();
        if( ! $post_id ) return;
        $post = get_post( $post_id );
        if( ! $post ) return '';
        $author = get_user_by( 'id', $post->post_author );
        return $author->user_email;
    }

    protected function user_id()
    {
        $current_user = wp_get_current_user();

        return ( $current_user ) ? $current_user->ID : '';
    }

    protected function user_first_name()
    {
        $current_user = wp_get_current_user();

        return ( $current_user ) ? $current_user->user_firstname : '';
    }

    protected function user_last_name()
    {
        $current_user = wp_get_current_user();

        return ( $current_user ) ? $current_user->user_lastname : '';
    }

    protected function user_display_name()
    {
        $current_user = wp_get_current_user();

        return ( $current_user ) ? $current_user->display_name : '';
    }

    protected function user_email()
    {
        $current_user = wp_get_current_user();

        return ( $current_user ) ? $current_user->user_email : '';
    }
    
    protected function admin_email()
    {
        return get_option( 'admin_email' );
    }

    protected function site_title()
    {
        return get_bloginfo( 'name' );
    }

    protected function site_url()
    {
        return get_bloginfo( 'url' );
    }


    protected function system_date()
    {
        $format = Ninja_Forms()->get_setting( 'date_format' );
        if ( empty( $format ) ) {
            $format = 'Y/m/d';
        }
        return date( $format, time() );
    }

    /**
     * Neutralize shortcode brackets to prevent shortcode injection.
     *
     * Converts [ and ] to HTML entities so they display correctly
     * but are not interpreted as shortcodes by do_shortcode().
     *
     * @see https://github.com/Saturday-Drive/ninja-forms/issues/8123
     *
     * @param mixed $value The value to sanitize.
     * @return mixed The sanitized value with brackets converted to entities.
     */
    protected function neutralize_shortcodes( $value )
    {
        if ( ! is_string( $value ) ) {
            return $value;
        }

        $value = str_replace( '[', '&#91;', $value );
        $value = str_replace( ']', '&#93;', $value );

        return $value;
    }

    protected function system_ip()
    {
        $ip = '127.0.0.1';
        if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        $ip = apply_filters( 'ninja_forms-get_ip', apply_filters( 'nf_get_ip', $ip ) );

        // Neutralize shortcode brackets to prevent shortcode injection.
        // @see https://github.com/Saturday-Drive/ninja-forms/issues/8123
        return $this->neutralize_shortcodes( $ip );
    }


} // END CLASS NF_MergeTags_System
