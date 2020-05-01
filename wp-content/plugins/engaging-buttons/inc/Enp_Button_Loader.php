<?php
/*
*   ENP_Button_Loader Class
*   For loading assets needed to run the Engaging Button
*
*/

/*
*
*   Check if we're requiring a user to be logged in to click
*   TODO: Move this to a cached object later?
*
*/
function enp_require_logged_in() {
    $require_logged_in = get_option('enp_button_must_be_logged_in');

    if(empty($require_logged_in) || $require_logged_in === 0 || $require_logged_in === false ) { // false. Not required
        $require_logged_in = false;
    } else {
        $require_logged_in = true;
    }

    return $require_logged_in;
}


/*
*
*   Quick check to see if a user should be allowed to click a button
*
*/
function enp_btn_clickable() {

    $logged_in = is_user_logged_in();
    $require_logged_in = enp_require_logged_in();
    if($require_logged_in === true && $logged_in === false) {
        $enp_btn_clickable = false;
    } else {
        $enp_btn_clickable = true;
    }

    return $enp_btn_clickable;

}


/*
*
*   Load our scripts and set-up styles and parameters
*
*/
class Enp_Button_Loader {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enp_btn_register_scripts'));
        add_action( 'wp_footer', array($this, 'enp_btn_svg_icons') );
    }

    /*
    *   Add SVG icons for use
    */
    public function enp_btn_svg_icons() {
        echo '<!-- SVG Icons for Engaging Buttons -->
<svg style="display: none;">
    <symbol id="enp-btn--user-has-not-clicked" viewBox="0 0 1024 1024">
        <path d="M819.2 512c0 28.314-2.458 51.2-30.771 51.2h-225.229v225.229c0 28.262-22.886 30.771-51.2 30.771s-51.2-2.509-51.2-30.771v-225.229h-225.229c-28.262 0-30.771-22.886-30.771-51.2s2.509-51.2 30.771-51.2h225.229v-225.229c0-28.314 22.886-30.771 51.2-30.771s51.2 2.458 51.2 30.771v225.229h225.229c28.314 0 30.771 22.886 30.771 51.2z"></path>
    </symbol>
    <symbol id="enp-btn--user-clicked" viewBox="0 0 1024 1024">
        <path class="path1" d="M424.653 870.298c-22.272 0-43.366-10.394-56.883-28.314l-182.938-241.715c-23.808-31.386-17.613-76.083 13.824-99.891 31.488-23.91 76.186-17.613 99.994 13.824l120.371 158.925 302.643-485.99c20.838-33.382 64.87-43.622 98.355-22.784 33.434 20.787 43.725 64.819 22.835 98.304l-357.581 573.952c-12.39 20.019-33.843 32.512-57.344 33.587-1.126 0.102-2.15 0.102-3.277 0.102z"></path>
    </symbol>
</svg>
';
    }


    /*
    *
    *   Register and enqueue style sheet & scripts.
    *
    */
    public function enp_btn_register_scripts() {
        $version = '1.0.4';

        // get our style choice from the database
        $enp_btn_style = get_option('enp_button_style');
        if(!empty($enp_btn_style)) {
            $style_path = plugins_url( 'engaging-buttons/front-end/css/enp-button-'.$enp_btn_style.'.min.css' );
        } else {
            $style_path = plugins_url( 'engaging-buttons/front-end/css/enp-button-plain-styles.min.css' );
        }

        wp_register_style( 'enp-button-style', $style_path, array(), $version);
        wp_enqueue_style( 'enp-button-style' );

        // add custom button color CSS, if necessary
        $enp_button_css = get_option('enp_button_color_css');
        if($enp_button_css !== false && !empty($enp_button_css) ) {
            wp_add_inline_style( 'enp-button-style', $enp_button_css );
        }

        $enp_button_font = get_option('enp_button_font');
        if($enp_button_font === 'open_sans') {
            wp_register_style( 'open_sans', 'https://fonts.googleapis.com/css?family=Open+Sans:600');
            wp_enqueue_style( 'open_sans' );
            // yeah, 14.28px is weird for a font size, but it's what WordPress Admin computes it to
            // and we want it to match what people see on the admin panel
            $enp_open_sans_css = '
body .enp-btns-wrap .enp-btn {
    font-family: "Open Sans", Helvetica Neue, Helvetica, Arial, sans-serif;
    font-weight: 600;
    font-size: 14.28px;
}

body .enp-btns-wrap .enp-btn-wrap {
    margin-top: 6px;
}';
            wp_add_inline_style( 'enp-button-style', $enp_open_sans_css );
        }



        wp_register_script( 'enp-button-scripts', plugins_url( 'engaging-buttons/front-end/js/scripts.min.js' ), array( 'jquery' ), $version, true);
        wp_enqueue_script( 'enp-button-scripts' );



        // in JavaScript, object properties are accessed as enp_button_params.ajax_url, enp_button_params.attr_name
        // This writes the params to the document
        // Get the protocol of the current page
        $protocol = isset( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';

        $is_enp_btn_clickable = enp_btn_clickable();
        if($is_enp_btn_clickable === false) {
            $is_enp_btn_clickable = 0;
        } else {
            $is_enp_btn_clickable = 1;
        }

        $user_id = get_current_user_id(); // will return 0 if none found

        $login_url = wp_login_url( get_permalink() );

        wp_localize_script( 'enp-button-scripts', 'enp_button_params',
            array( 'ajax_url' => admin_url( 'admin-ajax.php', $protocol ), 'enp_btn_clickable'=> $is_enp_btn_clickable, 'enp_login_url'=>$login_url, 'enp_btn_user_id'=> $user_id) );
        }
}

// fire up our styles and scripts
$init = new Enp_Button_Loader();

?>
