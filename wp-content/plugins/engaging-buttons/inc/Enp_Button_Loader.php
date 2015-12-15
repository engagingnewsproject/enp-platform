<?
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
    <symbol id="enp-icon-add" viewBox="0 0 1024 1024">
        <path d="M810 554h-256v256h-84v-256h-256v-84h256v-256h84v256h256v84z"></path>
    </symbol>
    <symbol id="enp-icon-close" viewBox="0 0 1024 1024">
        <path d="M810 274l-238 238 238 238-60 60-238-238-238 238-60-60 238-238-238-238 60-60 238 238 238-238z"></path>
    </symbol>
    <symbol id="enp-icon-check" viewBox="0 0 1024 1024">
        <path d="M384 690l452-452 60 60-512 512-238-238 60-60z"></path>
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
        wp_register_style( 'enp-button-style', plugins_url( 'engaging-buttons/front-end/css/enp-button-style.css' ));
        wp_enqueue_style( 'enp-button-style' );


        wp_register_script( 'enp-button-scripts', plugins_url( 'engaging-buttons/front-end/js/scripts.js' ), array( 'jquery' ), false, true);
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
